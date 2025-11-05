<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddon;
use App\Models\ProductVariant;
use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class BackorderResolutionService
{
    /**
     * Auto-process backorders when stock becomes available for a specific variant
     * This is called when stock_quantity is updated on a ProductVariant
     * 
     * @param int $variantId The variant ID that just received stock
     * @return array Results of the auto-processing
     */
    public function autoProcessBackordersForVariant(int $variantId): array
    {
        Log::info('BackorderResolutionService: Auto-processing backorders for variant', [
            'variant_id' => $variantId
        ]);

        return DB::transaction(function () use ($variantId) {
            $variant = ProductVariant::lockForUpdate()->find($variantId);
            
            if (!$variant || !$variant->track_inventory) {
                Log::info('BackorderResolutionService: Variant not found or does not track inventory', [
                    'variant_id' => $variantId
                ]);
                return ['success' => false, 'message' => 'Variant not found or does not track inventory'];
            }

            // Calculate available stock
            $availableStock = max(0, (int)$variant->stock_quantity - (int)$variant->reserved_stock);
            
            if ($availableStock <= 0) {
                Log::info('BackorderResolutionService: No available stock for variant', [
                    'variant_id' => $variantId,
                    'stock_quantity' => $variant->stock_quantity,
                    'reserved_stock' => $variant->reserved_stock
                ]);
                return ['success' => false, 'message' => 'No available stock'];
            }

            // Find all backordered items for this variant
            $backorderedItems = $this->findBackorderedItemsForVariant($variantId);
            
            if (empty($backorderedItems['items']) && empty($backorderedItems['addons'])) {
                Log::info('BackorderResolutionService: No backordered items found for variant', [
                    'variant_id' => $variantId
                ]);
                return ['success' => true, 'message' => 'No backordered items found', 'orders_processed' => []];
            }

            // Process backorders with available stock
            $result = $this->processBackordersWithStock(
                $variantId,
                $availableStock,
                $backorderedItems['items'],
                $backorderedItems['addons']
            );

            return $result;
        });
    }

    /**
     * Find all backordered items and addons for a specific variant
     */
    private function findBackorderedItemsForVariant(int $variantId): array
    {
        // Find order items with awaiting_stock status for this variant
        $items = OrderItem::where('variant_id', $variantId)
            ->where('fulfillment_status', 'awaiting_stock')
            ->whereHas('order', function ($query) {
                $query->whereIn('status', ['backordered', 'partially_backordered']);
            })
            ->with(['order'])  // Remove non-existent 'product' relationship
            ->orderBy('created_at', 'asc') // FIFO - First In First Out
            ->get();

        // Find order addons with awaiting_stock status for this variant
        $addons = OrderAddon::where('addon_variant_id', $variantId)
            ->where('fulfillment_status', 'awaiting_stock')
            ->whereHas('orderItem.order', function ($query) {
                $query->whereIn('status', ['backordered', 'partially_backordered']);
            })
            ->with(['orderItem.order'])  // Remove non-existent 'product' relationship
            ->orderBy('created_at', 'asc') // FIFO
            ->get();

        return [
            'items' => $items,
            'addons' => $addons
        ];
    }

    /**
     * Process backorders with available stock
     * Must wait for ALL items in an order to have sufficient stock before processing
     */
    private function processBackordersWithStock(
        int $variantId,
        int $availableStock,
        $items,
        $addons
    ): array {
        $ordersToCheck = collect();
        $processedOrders = [];
        $stockUsed = 0;
        $variant = ProductVariant::lockForUpdate()->find($variantId);

        // Group items and addons by order
        $itemsByOrder = $items->groupBy('order_id');
        $addonsByOrder = $addons->groupBy(function ($addon) {
            return $addon->orderItem->order_id;
        });

        // Merge all order IDs
        $allOrderIds = $itemsByOrder->keys()->merge($addonsByOrder->keys())->unique();

        foreach ($allOrderIds as $orderId) {
            $order = Order::find($orderId);
            if (!$order) continue;

            // Get all items and addons for this order with the specific variant
            $orderItems = $itemsByOrder->get($orderId, collect());
            $orderAddons = $addonsByOrder->get($orderId, collect());

            // Calculate total needed for this variant in this order
            $totalNeeded = 0;
            foreach ($orderItems as $item) {
                $needed = max(0, (int)$item->quantity - (int)$item->reserved_quantity);
                $totalNeeded += $needed;
            }
            foreach ($orderAddons as $addon) {
                $needed = max(0, (int)$addon->quantity - (int)$addon->reserved_quantity);
                $totalNeeded += $needed;
            }

            // Check if we have enough stock for this order's variant requirement
            if ($totalNeeded > 0 && ($stockUsed + $totalNeeded) <= $availableStock) {
                // Reserve the additional stock
                foreach ($orderItems as $item) {
                    $needed = max(0, (int)$item->quantity - (int)$item->reserved_quantity);
                    if ($needed > 0) {
                        $item->reserved_quantity = (int)$item->reserved_quantity + $needed;
                        
                        // **IMMEDIATE UPDATE**: If item is now fully reserved, update fulfillment_status
                        if ((int)$item->reserved_quantity >= (int)$item->quantity && $item->fulfillment_status === 'awaiting_stock') {
                            // Use the isShippable() method instead of trying to access product relationship
                            $isShippable = $item->isShippable();
                            $newStatus = $isShippable ? 'pending' : 'awaiting_handover';
                            
                            $item->fulfillment_status = $newStatus;
                            
                            Log::info('BackorderResolutionService: Updated item fulfillment status immediately', [
                                'order_item_id' => $item->id,
                                'order_id' => $orderId,
                                'old_status' => 'awaiting_stock',
                                'new_status' => $newStatus,
                                'is_shippable' => $isShippable
                            ]);
                        }
                        
                        $item->save();
                        $stockUsed += $needed;

                        Log::info('BackorderResolutionService: Reserved additional stock for order item', [
                            'order_item_id' => $item->id,
                            'order_id' => $orderId,
                            'variant_id' => $variantId,
                            'additional_reserved' => $needed,
                            'new_reserved_quantity' => $item->reserved_quantity,
                            'fulfillment_status' => $item->fulfillment_status
                        ]);
                    }
                }

                foreach ($orderAddons as $addon) {
                    $needed = max(0, (int)$addon->quantity - (int)$addon->reserved_quantity);
                    if ($needed > 0) {
                        $addon->reserved_quantity = (int)$addon->reserved_quantity + $needed;
                        
                        // **IMMEDIATE UPDATE**: If addon is now fully reserved, update fulfillment_status
                        if ((int)$addon->reserved_quantity >= (int)$addon->quantity && $addon->fulfillment_status === 'awaiting_stock') {
                            // Use the isShippable() method instead of trying to access product relationship
                            $isShippable = $addon->isShippable();
                            $newStatus = $isShippable ? 'pending' : 'awaiting_handover';
                            
                            $addon->fulfillment_status = $newStatus;
                            
                            Log::info('BackorderResolutionService: Updated addon fulfillment status immediately', [
                                'order_addon_id' => $addon->id,
                                'order_id' => $orderId,
                                'old_status' => 'awaiting_stock',
                                'new_status' => $newStatus,
                                'is_shippable' => $isShippable
                            ]);
                        }
                        
                        $addon->save();
                        $stockUsed += $needed;

                        Log::info('BackorderResolutionService: Reserved additional stock for order addon', [
                            'order_addon_id' => $addon->id,
                            'order_id' => $orderId,
                            'variant_id' => $variantId,
                            'additional_reserved' => $needed,
                            'new_reserved_quantity' => $addon->reserved_quantity,
                            'fulfillment_status' => $addon->fulfillment_status
                        ]);
                    }
                }

                // Add order to list to check if ALL items are ready
                $ordersToCheck->push($order);
            }
        }

        // Update variant stock
        // Note: We do NOT increment reserved_stock here because:
        // - reserved_stock is for pending orders (cart reservations)
        // - Backorder items are moving to processing status (allocated for fulfillment)
        // - The stock is directly allocated, not "reserved"
        if ($stockUsed > 0) {
            $oldStock = (int)$variant->stock_quantity;
            $oldReserved = (int)$variant->reserved_stock;
            
            $variant->stock_quantity = max(0, (int)$variant->stock_quantity - $stockUsed);
            // Do NOT update reserved_stock - backorder items go directly to processing/fulfillment
            $variant->save();

            // Log the reservation
            InventoryLog::create([
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'quantity' => $stockUsed,
                'action' => 'backorder_reservation',
                'reason' => 'Stock reserved for backordered items (auto-processing)',
                'reference_id' => null,
                'reference_type' => null,
                'user_id' => Auth::id() ?: null,
                'stock_after' => (int)$variant->stock_quantity,
                'reserved_after' => (int)$variant->reserved_stock
            ]);

            Log::info('BackorderResolutionService: Updated variant stock', [
                'variant_id' => $variantId,
                'stock_used' => $stockUsed,
                'old_stock' => $oldStock,
                'new_stock' => $variant->stock_quantity,
                'old_reserved' => $oldReserved,
                'new_reserved' => $variant->reserved_stock
            ]);
        }

        // Check each order to see if ALL items now have sufficient stock
        foreach ($ordersToCheck as $order) {
            if ($this->checkAndProcessOrder($order)) {
                $processedOrders[] = [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => 'moved_to_processing'
                ];
            }
        }

        return [
            'success' => true,
            'variant_id' => $variantId,
            'stock_used' => $stockUsed,
            'orders_processed' => $processedOrders
        ];
    }

    /**
     * Check if ALL items in an order have sufficient stock and auto-process if ready
     * This checks ALL variants in the order, not just the one that was restocked
     */
    private function checkAndProcessOrder(Order $order): bool
    {
        Log::info('BackorderResolutionService: Checking if order is ready for processing', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'current_status' => $order->status
        ]);

        // Refresh order to get latest item status
        $order->refresh();
        
        // Get all items and addons for this order
        $allItems = $order->items()->get();
        $allAddons = OrderAddon::whereHas('orderItem', function ($query) use ($order) {
            $query->where('order_id', $order->id);
        })->get();

        // Check if ALL items and addons are no longer awaiting_stock
        // (status should be pending, awaiting_handover, or further along)
        foreach ($allItems as $item) {
            if ($item->fulfillment_status === 'awaiting_stock') {
                Log::info('BackorderResolutionService: Order not ready - item still awaiting stock', [
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'variant_id' => $item->variant_id,
                    'fulfillment_status' => $item->fulfillment_status,
                    'quantity' => $item->quantity,
                    'reserved_quantity' => $item->reserved_quantity
                ]);
                return false;
            }
        }

        foreach ($allAddons as $addon) {
            if ($addon->fulfillment_status === 'awaiting_stock') {
                Log::info('BackorderResolutionService: Order not ready - addon still awaiting stock', [
                    'order_id' => $order->id,
                    'order_addon_id' => $addon->id,
                    'variant_id' => $addon->addon_variant_id,
                    'fulfillment_status' => $addon->fulfillment_status,
                    'quantity' => $addon->quantity,
                    'reserved_quantity' => $addon->reserved_quantity
                ]);
                return false;
            }
        }

        // ALL items and addons have sufficient stock - auto-process the order
        Log::info('BackorderResolutionService: All items have sufficient stock, auto-processing order', [
            'order_id' => $order->id,
            'order_number' => $order->order_number
        ]);

        return $this->moveOrderToProcessing($order);
    }

    /**
     * Move order from backordered/partially_backordered to processing
     * Fulfillment_status is already updated when reserved_qty is set, so just update order status
     */
    private function moveOrderToProcessing(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            $oldStatus = $order->status;

            // Double-check: Update any remaining items with awaiting_stock (safety check)
            $items = $order->items()->where('fulfillment_status', 'awaiting_stock')->get();
            foreach ($items as $item) {
                // This should rarely happen since we update status immediately now
                if ((int)$item->reserved_quantity >= (int)$item->quantity) {
                    // Use the isShippable() method instead of trying to access product relationship
                    $isShippable = $item->isShippable();
                    
                    $newStatus = $isShippable ? 'pending' : 'awaiting_handover';
                    $item->update(['fulfillment_status' => $newStatus]);

                    Log::info('BackorderResolutionService: Updated item fulfillment status (safety check)', [
                        'order_item_id' => $item->id,
                        'order_id' => $order->id,
                        'old_status' => 'awaiting_stock',
                        'new_status' => $newStatus,
                        'is_shippable' => $isShippable
                    ]);
                }
            }

            // Double-check: Update any remaining addons with awaiting_stock (safety check)
            $addons = OrderAddon::whereHas('orderItem', function ($query) use ($order) {
                $query->where('order_id', $order->id);
            })->where('fulfillment_status', 'awaiting_stock')->get();

            foreach ($addons as $addon) {
                // This should rarely happen since we update status immediately now
                if ((int)$addon->reserved_quantity >= (int)$addon->quantity) {
                    // Use the isShippable() method instead of trying to access product relationship
                    $isShippable = $addon->isShippable();
                    
                    $newStatus = $isShippable ? 'pending' : 'awaiting_handover';
                    $addon->update(['fulfillment_status' => $newStatus]);

                    Log::info('BackorderResolutionService: Updated addon fulfillment status (safety check)', [
                        'order_addon_id' => $addon->id,
                        'order_id' => $order->id,
                        'old_status' => 'awaiting_stock',
                        'new_status' => $newStatus,
                        'is_shippable' => $isShippable
                    ]);
                }
            }

            // Update order status to processing
            $order->update(['status' => 'processing']);

            Log::info('BackorderResolutionService: Order status updated to processing', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'old_status' => $oldStatus,
                'new_status' => 'processing'
            ]);

            // Send notification
            // $this->sendBackorderResolvedNotification($order); // DISABLED: Email notifications are currently commented out

            return true;
        });
    }

    /**
     * Send notification when backorder is resolved
     * DISABLED: Email notifications are currently commented out
     */
    private function sendBackorderResolvedNotification(Order $order): void
    {
        Log::info('BackorderResolutionService: Sending backorder resolved notification', [
            'order_id' => $order->id,
            'order_number' => $order->order_number
        ]);
        
        try {
            // Send email notification to customer
            if ($order->user && $order->user->email) {
                \Mail::to($order->user->email)->send(new \App\Mail\BackorderResolved($order));
                
                Log::info('BackorderResolutionService: Email notification sent successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_email' => $order->user->email
                ]);
            }
            
            // Send email notification to admin
            $adminEmail = config('mail.admin_email', 'admin@wagington.com');
            if ($adminEmail) {
                \Mail::to($adminEmail)->send(new \App\Mail\BackorderResolved($order));
                
                Log::info('BackorderResolutionService: Admin email notification sent', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'admin_email' => $adminEmail
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('BackorderResolutionService: Failed to send backorder resolved notification', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get all backordered orders with stock availability information
     * Used for admin UI to display backordered orders
     */
    public function getBackorderedOrdersWithStockInfo(): array
    {
        $backorderedOrders = Order::whereIn('status', ['backordered', 'partially_backordered'])
            ->with(['items', 'items.addons', 'user'])
            ->orderBy('created_at', 'asc')
            ->get();

        $ordersWithStockInfo = [];

        foreach ($backorderedOrders as $order) {
            $stockInfo = $this->getOrderStockAvailability($order);
            
            $ordersWithStockInfo[] = [
                'order' => $order,
                'stock_info' => $stockInfo,
                'is_ready_to_process' => $stockInfo['all_items_ready']
            ];
        }

        return $ordersWithStockInfo;
    }

    /**
     * Get stock availability information for an order
     */
    private function getOrderStockAvailability(Order $order): array
    {
        $items = $order->items;
        $addons = OrderAddon::whereHas('orderItem', function ($query) use ($order) {
            $query->where('order_id', $order->id);
        })->get();

        $itemsStatus = [];
        $allItemsReady = true;

        foreach ($items as $item) {
            $needed = max(0, (int)$item->quantity - (int)$item->reserved_quantity);
            $variant = ProductVariant::find($item->variant_id);
            $available = $variant ? max(0, (int)$variant->stock_quantity - (int)$variant->reserved_stock) : 0;

            $isReady = $needed <= $available;
            if (!$isReady) {
                $allItemsReady = false;
            }

            $itemsStatus[] = [
                'type' => 'item',
                'id' => $item->id,
                'product_name' => $item->product_name,
                'variant_display_name' => $item->variant_display_name,
                'quantity' => $item->quantity,
                'reserved_quantity' => $item->reserved_quantity,
                'needed' => $needed,
                'available' => $available,
                'is_ready' => $isReady
            ];
        }

        foreach ($addons as $addon) {
            $needed = max(0, (int)$addon->quantity - (int)$addon->reserved_quantity);
            $variant = ProductVariant::find($addon->addon_variant_id);
            $available = $variant ? max(0, (int)$variant->stock_quantity - (int)$variant->reserved_stock) : 0;

            $isReady = $needed <= $available;
            if (!$isReady) {
                $allItemsReady = false;
            }

            $itemsStatus[] = [
                'type' => 'addon',
                'id' => $addon->id,
                'product_name' => $addon->addon_name,
                'variant_display_name' => $addon->addon_variant_display_name,
                'quantity' => $addon->quantity,
                'reserved_quantity' => $addon->reserved_quantity,
                'needed' => $needed,
                'available' => $available,
                'is_ready' => $isReady
            ];
        }

        return [
            'items' => $itemsStatus,
            'all_items_ready' => $allItemsReady
        ];
    }
}
