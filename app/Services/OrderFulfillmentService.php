<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAddon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderFulfillmentService
{
    /**
     * Parse the item identifier to extract type and ID
     */
    private function parseItemIdentifier(string $identifier): ?array
    {
        // Expected format: type_id (e.g., 'item_12' or 'addon_5')
        if (preg_match('/^(item|addon)_(\d+)$/', $identifier, $matches)) {
            return [
                'type' => $matches[1],
                'id' => (int)$matches[2]
            ];
        }
        return null;
    }

    /**
     * Get parsed identifiers and fetch corresponding entities
     */
    private function getEntities(array $itemIds): array
    {
        $parsed = [];
        foreach ($itemIds as $identifier) {
            $p = $this->parseItemIdentifier($identifier);
            if ($p) {
                $parsed[$identifier] = $p;
            }
        }
        
        $itemIdsToFetch = collect($parsed)->where('type', 'item')->pluck('id')->toArray();
        $addonIdsToFetch = collect($parsed)->where('type', 'addon')->pluck('id')->toArray();
        
        $items = OrderItem::whereIn('id', $itemIdsToFetch)->get()->keyBy('id');
        $addons = OrderAddon::with('orderItem')->whereIn('id', $addonIdsToFetch)->get()->keyBy('id');
        
        return [
            'parsed' => $parsed,
            'items' => $items,
            'addons' => $addons
        ];
    }

    /**
     * Mark order items as processing (picked/packaged)
     */
    public function markItemsAsProcessing(array $itemIds): array
    {
        $results = [];
        
        DB::transaction(function() use ($itemIds, &$results) {
            foreach ($itemIds as $identifier) {
                $parsed = $this->parseItemIdentifier($identifier);

                if (!$parsed) {
                    $results[$identifier] = ['success' => false, 'message' => 'Invalid item format'];
                    continue;
                }

                $type = $parsed['type'];
                $id = $parsed['id'];
                
                // Dispatch the action based on the clear type
                switch ($type) {
                    case 'item':
                        $entity = OrderItem::find($id);
                        $entityName = 'Main item';
                        break;
                    case 'addon':
                        $entity = OrderAddon::find($id);
                        $entityName = 'Addon';
                        break;
                    default:
                        $entity = null; // Should be covered by regex, but safe for logic
                }

                if (!$entity) {
                    $results[$identifier] = ['success' => false, 'message' => "$entityName not found"];
                    continue;
                }
                
                // --- Unified Processing Logic ---
                if ($entity->fulfillment_status === 'pending') {
                    $entity->update([
                        'fulfillment_status' => 'processing',
                        'fulfilled_quantity' => 0
                    ]);
                    $results[$identifier] = ['success' => true, 'message' => "$entityName marked as processing"];
                    
                    Log::info('OrderFulfillmentService: Item marked as processing', [
                        'type' => $type,
                        'id' => $id,
                        'order_id' => $type === 'item' ? $entity->order_id : $entity->orderItem->order_id,
                    ]);
                } else {
                    $results[$identifier] = ['success' => false, 'message' => "$entityName is not in pending status"];
                }
                // ---------------------------------
            }
            
            // Update overall order status
            $this->updateOrderStatuses($itemIds);
        });
        
        return $results;
    }
    
    /**
     * Mark order items as shipped
     */
    public function markItemsAsShipped(array $itemIds, ?string $trackingNumber = null, ?string $carrier = null): array
    {
        $results = [];
        
        DB::transaction(function() use ($itemIds, $trackingNumber, $carrier, &$results) {
            foreach ($itemIds as $identifier) {
                $parsed = $this->parseItemIdentifier($identifier);

                if (!$parsed) {
                    $results[$identifier] = ['success' => false, 'message' => 'Invalid item format'];
                    continue;
                }

                $type = $parsed['type'];
                $id = $parsed['id'];
                
                // Dispatch the action based on the clear type
                switch ($type) {
                    case 'item':
                        $entity = OrderItem::find($id);
                        $entityName = 'Main item';
                        break;
                    case 'addon':
                        $entity = OrderAddon::find($id);
                        $entityName = 'Addon';
                        break;
                    default:
                        $entity = null; // Should be covered by regex, but safe for logic
                }

                if (!$entity) {
                    $results[$identifier] = ['success' => false, 'message' => "$entityName not found"];
                    continue;
                }
                
                // Validate status transition
                if ($entity->fulfillment_status !== 'processing') {
                    $results[$identifier] = ['success' => false, 'message' => "$entityName must be processing before being marked as shipped"];
                    continue;
                }
                
                // Update item
                $entity->update([
                    'fulfillment_status' => 'shipped',
                    'fulfilled_quantity' => $entity->quantity
                ]);
                
                $results[$identifier] = ['success' => true, 'message' => "$entityName marked as shipped"];
                
                Log::info('OrderFulfillmentService: Item marked as shipped', [
                    'type' => $type,
                    'id' => $id,
                    'order_id' => $type === 'item' ? $entity->order_id : $entity->orderItem->order_id,
                    'tracking_number' => $trackingNumber,
                ]);
            }
            
            // Update order tracking information
            if ($trackingNumber) {
                $this->updateOrderTracking($itemIds, $trackingNumber, $carrier);
            }
            
            // Update overall order status
            $this->updateOrderStatuses($itemIds);
        });
        
        return $results;
    }
    
    /**
     * Mark order items as delivered
     */
    public function markItemsAsDelivered(array $itemIds): array
    {
        $results = [];
        
        DB::transaction(function() use ($itemIds, &$results) {
            $entities = $this->getEntities($itemIds);
            
            foreach ($itemIds as $identifier) {
                if (!isset($entities['parsed'][$identifier])) {
                    $results[$identifier] = ['success' => false, 'message' => 'Invalid item format'];
                    continue;
                }

                $parsed = $entities['parsed'][$identifier];
                $type = $parsed['type'];
                $id = $parsed['id'];
                $entityName = $type === 'item' ? 'Main item' : 'Addon';
                
                // Get entity from pre-fetched collections
                $entity = $type === 'item' 
                    ? $entities['items'][$id] ?? null
                    : $entities['addons'][$id] ?? null;

                if (!$entity) {
                    $results[$identifier] = ['success' => false, 'message' => "$entityName not found"];
                    continue;
                }
                
                // Validate status transition
                if ($entity->fulfillment_status !== 'shipped') {
                    $results[$identifier] = ['success' => false, 'message' => "$entityName must be shipped before being marked as delivered"];
                    continue;
                }
                
                // Update item
                $entity->update([
                    'fulfillment_status' => 'delivered',
                    'fulfilled_quantity' => $entity->quantity
                ]);
                
                $results[$identifier] = ['success' => true, 'message' => "$entityName marked as delivered"];
                
                Log::info('OrderFulfillmentService: Item marked as delivered', [
                    'type' => $type,
                    'id' => $id,
                    'order_id' => $type === 'item' ? $entity->order_id : $entity->orderItem->order_id,
                ]);
            }
            
            // Update overall order status
            $this->updateOrderStatuses($itemIds);
        });
        
        return $results;
    }
    
    /**
     * Mark non-shippable items as handed over to customer
     */
    public function markItemsAsHandedOver(array $itemIds): array
    {
        $results = [];
        
        DB::transaction(function() use ($itemIds, &$results) {
            foreach ($itemIds as $identifier) {
                $parsed = $this->parseItemIdentifier($identifier);

                if (!$parsed) {
                    $results[$identifier] = ['success' => false, 'message' => 'Invalid item format'];
                    continue;
                }

                $type = $parsed['type'];
                $id = $parsed['id'];
                
                switch ($type) {
                    case 'item':
                        $entity = OrderItem::find($id);
                        $entityName = 'Main item';
                        break;
                    case 'addon':
                        $entity = OrderAddon::find($id);
                        $entityName = 'Addon';
                        break;
                    default:
                        $entity = null;
                }

                if (!$entity) {
                    $results[$identifier] = ['success' => false, 'message' => "$entityName not found"];
                    continue;
                }
                
                // Only allow marking items with awaiting_handover status
                if ($entity->fulfillment_status !== 'awaiting_handover') {
                    $results[$identifier] = ['success' => false, 'message' => "$entityName is not in awaiting handover status"];
                    continue;
                }
                
                // Update item to handed_over status
                $entity->update([
                    'fulfillment_status' => 'handed_over',
                    'fulfilled_quantity' => $entity->quantity
                ]);
                
                $results[$identifier] = ['success' => true, 'message' => "$entityName marked as handed over"];
                
                Log::info('OrderFulfillmentService: Item marked as handed over', [
                    'type' => $type,
                    'id' => $id,
                    'order_id' => $type === 'item' ? $entity->order_id : $entity->orderItem->order_id,
                ]);
            }
            
            // Update overall order status
            $this->updateOrderStatuses($itemIds);
        });
        
        return $results;
    }
    
    /**
     * Update order tracking information
     */
    private function updateOrderTracking(array $itemIds, string $trackingNumber, ?string $carrier = null): void
    {
        // Parse identifiers to get actual IDs
        $parsedIds = [];
        foreach ($itemIds as $identifier) {
            $parsed = $this->parseItemIdentifier($identifier);
            if ($parsed) {
                $parsedIds[] = $parsed;
            }
        }
        
        // Get order IDs from both order items and addons
        $orderItemIds = collect($parsedIds)
            ->where('type', 'item')
            ->pluck('id')
            ->toArray();
            
        $addonOrderItemIds = OrderAddon::whereIn('id', 
            collect($parsedIds)
                ->where('type', 'addon')
                ->pluck('id')
                ->toArray()
        )->pluck('order_item_id')->toArray();
        
        $allOrderItemIds = array_merge($orderItemIds, $addonOrderItemIds);
        $orderIds = OrderItem::whereIn('id', $allOrderItemIds)
            ->pluck('order_id')
            ->unique();
        
        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $updateData = ['tracking_number' => $trackingNumber];
                if ($carrier) {
                    $updateData['shipping_method'] = $carrier;
                }
                
                $order->update($updateData);
            }
        }
    }
    
    /**
     * Update overall order status based on item fulfillment
     */
    private function updateOrderStatuses(array $itemIds): void
    {
        // Parse identifiers to get actual IDs
        $parsedIds = [];
        foreach ($itemIds as $identifier) {
            $parsed = $this->parseItemIdentifier($identifier);
            if ($parsed) {
                $parsedIds[] = $parsed;
            }
        }
        
        // Get order IDs from both order items and addons
        $orderItemIds = collect($parsedIds)
            ->where('type', 'item')
            ->pluck('id')
            ->toArray();
            
        $addonOrderItemIds = OrderAddon::whereIn('id', 
            collect($parsedIds)
                ->where('type', 'addon')
                ->pluck('id')
                ->toArray()
        )->pluck('order_item_id')->toArray();
        
        $allOrderItemIds = array_merge($orderItemIds, $addonOrderItemIds);
        $orderIds = OrderItem::whereIn('id', $allOrderItemIds)
            ->pluck('order_id')
            ->unique();
        
        foreach ($orderIds as $orderId) {
            $this->updateOrderStatus($orderId);
        }
    }
    
    /**
     * Update individual order status
     */
    public function updateOrderStatus(int $orderId): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            return;
        }
        
        // Get all order items and their fulfillment status
        $orderItems = $order->items;
        $addons = OrderAddon::whereHas('orderItem', function($query) use ($orderId) {
            $query->where('order_id', $orderId);
        })->get();
        
        // Check if all items are fulfilled
        $allItemsFulfilled = $this->areAllItemsFulfilled($orderItems, $addons);
        
        // Check if order has any delivered items (regardless of allItemsFulfilled)
        $hasDeliveredItems = $orderItems->where('fulfillment_status', 'delivered')->count() > 0 ||
                            $addons->where('fulfillment_status', 'delivered')->count() > 0;
        
        // If order has delivered items and current status is not already delivered or completed
        if ($hasDeliveredItems && !in_array($order->status, ['delivered', 'completed'])) {
            $order->update(['status' => 'delivered']);
            Log::info('OrderFulfillmentService: Order marked as delivered', [
                'order_id' => $orderId,
                'order_number' => $order->order_number,
            ]);
        }
        
        // Now check if all items are fulfilled for completion
        if ($allItemsFulfilled) {
            // Check what types of items we have
            $hasDeliveredItems = false;
            $hasHandedOverItems = false;
            
            // Check main order items
            foreach ($orderItems as $item) {
                if ($item->fulfillment_status === 'delivered') {
                    $hasDeliveredItems = true;
                } elseif ($item->fulfillment_status === 'handed_over') {
                    $hasHandedOverItems = true;
                }
            }
            
            // Check addons
            foreach ($addons as $addon) {
                if ($addon->fulfillment_status === 'delivered') {
                    $hasDeliveredItems = true;
                } elseif ($addon->fulfillment_status === 'handed_over') {
                    $hasHandedOverItems = true;
                }
            }
            
            // If order has only non-shippable items (all handed_over) -> completed
            if ($hasHandedOverItems && !$hasDeliveredItems) {
                $order->update(['status' => 'completed']);
                Log::info('OrderFulfillmentService: Order marked as completed (non-shippable only)', [
                    'order_id' => $orderId,
                    'order_number' => $order->order_number,
                ]);
            }
            // If order has delivered items -> first set to delivered, then check if should be completed
            elseif ($hasDeliveredItems) {
                $order->update(['status' => 'delivered']);
                Log::info('OrderFulfillmentService: Order marked as delivered', [
                    'order_id' => $orderId,
                    'order_number' => $order->order_number,
                ]);
                
                // Check if all items are in final state (all delivered OR all handed_over OR mix)
                // For delivered items, check if all items in order are delivered
                $allMainItemsDelivered = $orderItems->where('fulfillment_status', 'delivered')->count() === $orderItems->count();
                $allAddonsDelivered = $addons->count() === 0 || $addons->where('fulfillment_status', 'delivered')->count() === $addons->count();
                
                // If all items are delivered, mark as completed
                if ($allMainItemsDelivered && $allAddonsDelivered && !$hasHandedOverItems) {
                    $order->update(['status' => 'completed']);
                    Log::info('OrderFulfillmentService: Order marked as completed (all items delivered)', [
                        'order_id' => $orderId,
                        'order_number' => $order->order_number,
                    ]);
                }
                // If mixed order (has delivered + handed_over), also mark as completed
                elseif ($hasHandedOverItems) {
                    $order->update(['status' => 'completed']);
                    Log::info('OrderFulfillmentService: Order marked as completed (mixed delivered + handed_over)', [
                        'order_id' => $orderId,
                        'order_number' => $order->order_number,
                    ]);
                }
            }
        }
        
        // Check if any items are shipped (for partially_shipped status)
        $hasShippedItems = $orderItems->where('fulfillment_status', 'shipped')->count() > 0 ||
                          $addons->where('fulfillment_status', 'shipped')->count() > 0;
        
        // Only set partially_shipped if not already in a later state
        if ($hasShippedItems && !in_array($order->status, ['delivered', 'completed'])) {
            $order->update(['status' => 'partially_shipped']);
        }
    }
    
    /**
     * Check if all items in an order are fulfilled
     * An item is considered fulfilled only if it has reached its final status:
     * - Shippable items must be 'delivered'
     * - Non-shippable items must be 'handed_over'
     * 'awaiting_handover' is NOT a final fulfilled status
     */
    private function areAllItemsFulfilled($orderItems, $addons): bool
    {
        // Check main order items - only delivered or handed_over count as fulfilled
        foreach ($orderItems as $item) {
            if (!in_array($item->fulfillment_status, ['delivered', 'handed_over'])) {
                return false;
            }
        }
        
        // Check addons - only delivered or handed_over count as fulfilled
        foreach ($addons as $addon) {
            if (!in_array($addon->fulfillment_status, ['delivered', 'handed_over'])) {
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Get fulfillment summary for an order
     */
    public function getFulfillmentSummary(Order $order): array
    {
        $orderItems = $order->items;
        $addons = OrderAddon::whereHas('orderItem', function($query) use ($order) {
            $query->where('order_id', $order->id);
        })->get();
        
        $summary = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'overall_status' => $order->status,
            'items' => [
                'total' => $orderItems->count(),
                'pending' => $orderItems->where('fulfillment_status', 'pending')->count(),
                'processing' => $orderItems->where('fulfillment_status', 'processing')->count(),
                'shipped' => $orderItems->where('fulfillment_status', 'shipped')->count(),
                'delivered' => $orderItems->where('fulfillment_status', 'delivered')->count(),
                'awaiting_handover' => $orderItems->where('fulfillment_status', 'awaiting_handover')->count(),
                'handed_over' => $orderItems->where('fulfillment_status', 'handed_over')->count(),
            ],
            'addons' => [
                'total' => $addons->count(),
                'pending' => $addons->where('fulfillment_status', 'pending')->count(),
                'processing' => $addons->where('fulfillment_status', 'processing')->count(),
                'shipped' => $addons->where('fulfillment_status', 'shipped')->count(),
                'delivered' => $addons->where('fulfillment_status', 'delivered')->count(),
                'awaiting_handover' => $addons->where('fulfillment_status', 'awaiting_handover')->count(),
                'handed_over' => $addons->where('fulfillment_status', 'handed_over')->count(),
            ],
            'is_fully_fulfilled' => $this->areAllItemsFulfilled($orderItems, $addons),
        ];
        
        return $summary;
    }
}