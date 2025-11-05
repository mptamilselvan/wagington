<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\OrderAddon;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number','user_id',
        'shipping_address_id','billing_address_id',
        'status','payment_failed_attempts',
        'shipping_method','tracking_number','estimated_delivery',
        'subtotal','coupon_discount_amount','strikethrough_discount_amount','tax_amount','shipping_amount','total_amount',
        'applied_tax_rate'
    ];

    protected $casts = [
        'payment_failed_attempts' => 'integer',
        'subtotal' => 'decimal:2',
        'coupon_discount_amount' => 'decimal:2',
        'strikethrough_discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'applied_tax_rate' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function appliedVouchers()
    {
        return $this->hasMany(OrderVoucher::class)->orderBy('stack_order');
    }

    public function addons()
    {
        return $this->hasManyThrough(
            OrderAddon::class,
            OrderItem::class,
            'order_id', // Foreign key on OrderItem
            'order_item_id', // Foreign key on OrderAddon
            'id', // Local key on Order
            'id' // Local key on OrderItem
        );
    }

    // Fulfillment helper methods
    public function shippableItems()
    {
        return $this->hasMany(OrderItem::class)->where('fulfillment_status', '!=', 'awaiting_handover');
    }

    public function nonShippableItems()
    {
        return $this->hasMany(OrderItem::class)->where('fulfillment_status', 'awaiting_handover');
    }

    /**
     * Get the fulfillment progress percentage for this order
     * 
     * Note: For best performance, eager load relationships before calling:
     * $order->load(['items', 'items.addons']) or Order::with(['items', 'items.addons'])->find($id)
     * 
     * @return float Percentage of items fulfilled (0-100)
     */
    public function getFulfillmentProgress()
    {
        // Ensure both items and their addons are loaded
        $this->loadMissing(['items', 'items.addons']);
        
        // Get all items and their addons from the loaded relationships
        $allItems = $this->items;
        $allAddons = $allItems->flatMap(function ($item) {
            return $item->addons ?? collect();
        });
        
        $totalItems = $allItems->count() + $allAddons->count();
        if ($totalItems === 0) {
            return 100;
        }
        
        $fulfilledItems = $allItems->whereIn('fulfillment_status', ['delivered', 'handed_over'])->count() +
                         $allAddons->whereIn('fulfillment_status', ['delivered', 'handed_over'])->count();
        
        return round(($fulfilledItems / $totalItems) * 100, 2);
    }

    /**
     * Check if the order is fully fulfilled (all items and addons are delivered or handed over)
     * 
     * Note: For best performance, eager load relationships before calling:
     * $order->load(['items', 'items.addons']) or Order::with(['items', 'items.addons'])->find($id)
     * 
     * @return bool True if all items and addons are fulfilled, false otherwise
     */
    public function isFullyFulfilled()
    {
        // If no items exist at all, order is not fulfilled
        if ($this->relationLoaded('items')) {
            $items = $this->items;
            if ($items->isEmpty()) {
                return false;
            }

            // Check items from loaded relation
            $pendingItemsCount = $items->whereNotIn('fulfillment_status', ['delivered', 'handed_over'])->count();
            
            // Check addons from loaded relations if available
            if ($items->first()?->relationLoaded('addons')) {
                $pendingAddonsCount = $items->flatMap(function ($item) {
                    return $item->addons;
                })->whereNotIn('fulfillment_status', ['delivered', 'handed_over'])->count();
            } else {
                // Fall back to query for addons if not loaded
                $pendingAddonsCount = OrderAddon::whereHas('orderItem', function($query) {
                    $query->where('order_id', $this->id);
                })
                ->whereNotIn('fulfillment_status', ['delivered', 'handed_over'])
                ->count();
            }
        } else {
            // Fall back to queries if relations aren't loaded
            $itemsExist = $this->items()->exists();
            if (!$itemsExist) {
                return false;
            }

            $pendingItemsCount = $this->items()
                ->whereNotIn('fulfillment_status', ['delivered', 'handed_over'])
                ->count();

            $pendingAddonsCount = OrderAddon::whereHas('orderItem', function($query) {
                $query->where('order_id', $this->id);
            })
            ->whereNotIn('fulfillment_status', ['delivered', 'handed_over'])
            ->count();
        }

        // Return true only if there are items and no pending items or addons
        return $pendingItemsCount === 0 && $pendingAddonsCount === 0;
    }

    /**
     * Get a comprehensive fulfillment summary for this order
     * 
     * Note: For best performance, eager load relationships before calling:
     * $order->load(['items', 'items.addons']) or Order::with(['items', 'items.addons'])->find($id)
     * 
     * @return array Fulfillment summary with counts and status
     */
    public function getFulfillmentSummary()
    {
        // Ensure both items and their addons are loaded
        $this->loadMissing(['items', 'items.addons']);

        // Get all items and their addons from the loaded relationships
        $items = $this->items;
        $addons = $items->flatMap(function ($item) {
            return $item->addons ?? collect();
        });

        // Get status counts using countBy for both items and addons
        $itemStatusCounts = $items->countBy('fulfillment_status');
        $addonStatusCounts = $addons->countBy('fulfillment_status');

        // Helper function to get count for a status with default 0
        $getCount = function($counts, $status) {
            return $counts[$status] ?? 0;
        };

        // Calculate fulfilled counts once
        $fulfilledItemsCount = $items->whereIn('fulfillment_status', ['delivered', 'handed_over'])->count();
        $fulfilledAddonsCount = $addons->whereIn('fulfillment_status', ['delivered', 'handed_over'])->count();
        
        // Calculate total counts
        $totalItems = $items->count();
        $totalAddons = $addons->count();
        $totalCount = $totalItems + $totalAddons;

        // Calculate progress percentage
        $progress = $totalCount === 0 ? 100 : 
            round((($fulfilledItemsCount + $fulfilledAddonsCount) / $totalCount) * 100, 2);
        
        return [
            'order_id' => $this->id,
            'order_number' => $this->order_number,
            'overall_status' => $this->status,
            'progress_percentage' => $progress,
            'is_fully_fulfilled' => ($totalCount === 0) || 
                                  ($fulfilledItemsCount + $fulfilledAddonsCount === $totalCount),
            'items' => [
                'total' => $totalItems,
                'awaiting_stock' => $getCount($itemStatusCounts, 'awaiting_stock'),
                'pending' => $getCount($itemStatusCounts, 'pending'),
                'processing' => $getCount($itemStatusCounts, 'processing'),
                'shipped' => $getCount($itemStatusCounts, 'shipped'),
                'delivered' => $getCount($itemStatusCounts, 'delivered'),
                'awaiting_handover' => $getCount($itemStatusCounts, 'awaiting_handover'),
                'handed_over' => $getCount($itemStatusCounts, 'handed_over'),
            ],
            'addons' => [
                'total' => $totalAddons,
                'awaiting_stock' => $getCount($addonStatusCounts, 'awaiting_stock'),
                'pending' => $getCount($addonStatusCounts, 'pending'),
                'processing' => $getCount($addonStatusCounts, 'processing'),
                'shipped' => $getCount($addonStatusCounts, 'shipped'),
                'delivered' => $getCount($addonStatusCounts, 'delivered'),
                'awaiting_handover' => $getCount($addonStatusCounts, 'awaiting_handover'),
                'handed_over' => $getCount($addonStatusCounts, 'handed_over'),
            ],
        ];
    }
}