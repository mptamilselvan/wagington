<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InventoryRestock extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;
    
    // Modal states
    public bool $showRestockModal = false;
    public ?int $selectedVariantId = null;
    public int $restockQuantity = 0;
    public string $restockReason = '';
    
    // Selected variant details
    public ?ProductVariant $selectedVariant = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    protected $rules = [
        'restockQuantity' => 'required|integer|min:1',
        'restockReason' => 'nullable|string|max:500',
    ];

    public function updatingSearch(): void 
    { 
        $this->resetPage(); 
    }

    public function openRestockModal(int $variantId): void
    {
        try {
            Log::info('InventoryRestock: Attempting to open restock modal', [
                'variant_id' => $variantId,
                'user_id' => Auth::id()
            ]);
            
            // Try to load the variant with its product
            $variant = ProductVariant::with('product')->find($variantId);
            
            // Check if variant exists
            if (!$variant) {
                $errorMsg = 'Variant not found.';
                $this->addError('selectedVariant', $errorMsg);
                Log::warning('InventoryRestock: Variant not found', [
                    'variant_id' => $variantId,
                    'user_id' => Auth::id()
                ]);
                return;
            }
            
            // Check if the product exists
            if (!$variant->product) {
                $errorMsg = 'Associated product not found.';
                $this->addError('selectedVariant', $errorMsg);
                Log::warning('InventoryRestock: Associated product not found', [
                    'variant_id' => $variantId,
                    'product_id' => $variant->product_id ?? null,
                    'user_id' => Auth::id()
                ]);
                return;
            }
            
            // Check if the variant itself tracks inventory (not the product)
            // The track_inventory field exists on the variant, not the product
            if (!$variant->track_inventory) {
                $errorMsg = 'This variant does not track inventory.';
                $this->addError('selectedVariant', $errorMsg);
                Log::warning('InventoryRestock: Variant does not track inventory', [
                    'variant_id' => $variantId,
                    'product_id' => $variant->product_id,
                    'track_inventory' => $variant->track_inventory,
                    'user_id' => Auth::id()
                ]);
                return;
            }

            // Proceed with modal setup only if all validations pass
            $this->selectedVariantId = $variantId;
            $this->selectedVariant = $variant;
            $this->restockQuantity = 0;
            $this->restockReason = '';
            $this->showRestockModal = true;
            $this->resetErrorBag();
            
            Log::info('InventoryRestock: Successfully opened restock modal', [
                'variant_id' => $variantId,
                'product_name' => $variant->product->name ?? 'Unknown',
                'sku' => $variant->sku ?? 'Unknown',
                'user_id' => Auth::id()
            ]);
        } catch (\Exception $e) {
            $errorMsg = 'An error occurred while opening the restock modal.';
            $this->addError('selectedVariant', $errorMsg);
            Log::error('InventoryRestock: Error opening restock modal', [
                'variant_id' => $variantId ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
        }
    }

    public function closeRestockModal(): void
    {
        Log::info('InventoryRestock: Closing restock modal', [
            'selected_variant_id' => $this->selectedVariantId,
            'user_id' => Auth::id()
        ]);
        
        $this->showRestockModal = false;
        $this->selectedVariantId = null;
        $this->selectedVariant = null;
        $this->restockQuantity = 0;
        $this->restockReason = '';
        $this->resetErrorBag();
    }

    public function restock(): void
    {
        $this->validate();

        if (!$this->selectedVariant) {
            $this->addError('variant', 'Variant not found.');
            return;
        }

        try {
            Log::info('InventoryRestock: Starting restock process', [
                'variant_id' => $this->selectedVariant->id,
                'quantity' => $this->restockQuantity,
                'user_id' => Auth::id()
            ]);
            
            // Capture needed values outside transaction
            $variantId = $this->selectedVariant->id;
            $restockQty = $this->restockQuantity;
            $reason = $this->restockReason;
            $productId = $this->selectedVariant->product_id;
            $productName = $this->selectedVariant->product->name;

            \DB::transaction(function () use ($variantId, $restockQty, $reason, $productId, $productName) {
                // Re-fetch the variant with a lock
                $lockedVariant = ProductVariant::lockForUpdate()->find($variantId);
                
                if (!$lockedVariant) {
                    throw new \Exception('Variant not found or was deleted');
                }

                // Get old values for logging from locked instance
                $oldStock = (int)$lockedVariant->stock_quantity;
                $oldReserved = (int)$lockedVariant->reserved_stock;

                // Update stock quantity on locked instance
                $lockedVariant->stock_quantity = $oldStock + $restockQty;
                $lockedVariant->save();

                // Log the restock action using locked values
                InventoryLog::create([
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => $restockQty,
                    'action' => 'stock_in',
                    'reason' => $reason ?: 'Manual restock via admin panel',
                    'reference_id' => null,
                    'reference_type' => null,
                    'user_id' => Auth::id(),
                    'stock_after' => (int)$lockedVariant->stock_quantity,
                    'reserved_after' => $oldReserved
                ]);

                Log::info('InventoryRestock: Stock added successfully', [
                    'variant_id' => $variantId,
                    'product_name' => $productName,
                    'old_stock' => $oldStock,
                    'new_stock' => $lockedVariant->stock_quantity,
                    'quantity_added' => $restockQty,
                    'user_id' => Auth::id()
                ]);
            });

            // The ProductVariantObserver will automatically trigger backorder resolution
            // after the transaction commits
            
            session()->flash('success', 'Stock added successfully! Backorders are being processed automatically.');
            
            Log::info('InventoryRestock: Restock process completed successfully', [
                'variant_id' => $this->selectedVariant->id,
                'quantity_added' => $this->restockQuantity,
                'user_id' => Auth::id()
            ]);
            
            $this->closeRestockModal();
            
        } catch (\Exception $e) {
            Log::error('InventoryRestock: Failed to add stock', [
                'variant_id' => $this->selectedVariantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            
            $this->addError('restock', 'Failed to add stock: ' . $e->getMessage());
        }
    }

    public function render()
    {
        try {
            $query = ProductVariant::query()
                ->select('product_variants.*')
                ->with(['product'])
                ->where('track_inventory', true)  // Filter variants that track inventory
                // Calculate awaiting stock quantity for MAIN ITEMS
                ->addSelect([
                    'awaiting_stock_quantity_items' => \DB::table('order_items')
                        ->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0)')
                        ->whereColumn('order_items.variant_id', 'product_variants.id')
                        ->where('order_items.fulfillment_status', 'awaiting_stock')
                        ->whereIn('order_items.order_id', function($query) {
                            $query->select('id')
                                ->from('orders')
                                ->whereIn('status', ['backordered', 'partially_backordered']);
                        })
                ])
                // Calculate awaiting stock quantity for ADDONS
                ->addSelect([
                    'awaiting_stock_quantity_addons' => \DB::table('order_addons')
                        ->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0)')
                        ->whereColumn('order_addons.addon_variant_id', 'product_variants.id')
                        ->where('order_addons.fulfillment_status', 'awaiting_stock')
                        ->whereIn('order_addons.order_item_id', function($query) {
                            $query->select('order_items.id')
                                ->from('order_items')
                                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                                ->whereIn('orders.status', ['backordered', 'partially_backordered']);
                        })
                ]);
                // Total sold quantity is already tracked in the sold_stock column
                // This includes both main items and add-ons, updated during checkout

            if ($this->search !== '') {
                $s = '%' . trim($this->search) . '%';
                $query->where(function ($q) use ($s) {
                    $q->where('sku', 'like', $s)
                      ->orWhereHas('product', function ($pq) use ($s) {
                          $pq->where('name', 'like', $s);
                      });
                });
            }

            $variants = $query->orderBy('updated_at', 'desc')->paginate($this->perPage);

            return view('livewire.backend.inventory-restock', [
                'variants' => $variants,
            ])->layout('layouts.backend.index');
        } catch (\Exception $e) {
            Log::error('InventoryRestock: Error rendering component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            
            // Return a simple error view
            return view('livewire.backend.inventory-restock', [
                'variants' => collect(),
            ])->layout('layouts.backend.index');
        }
    }
}