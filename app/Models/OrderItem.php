<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id','product_id','variant_id','product_name','variant_display_name','sku','product_attributes','quantity','reserved_quantity','fulfilled_quantity','fulfillment_status','unit_price','total_price'
    ];

    protected $casts = [
        'product_attributes' => 'array',
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'fulfilled_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function addons()
    {
        return $this->hasMany(OrderAddon::class);
    }

    // Helper methods for fulfillment
    public function isShippable()
    {
        return $this->fulfillment_status !== 'awaiting_handover';
    }

    public function isFulfilled()
    {
        return in_array($this->fulfillment_status, ['delivered', 'handed_over']);
    }

    /**
     * Calculate the fulfillment progress as a percentage
     * 
     * @return float Percentage of quantity fulfilled (0-100), rounded to 2 decimal places
     */
    public function getFulfillmentProgress()
    {
        // Early return for digital items that are handed over
        if ($this->fulfillment_status === 'handed_over') {
            return 100.00;
        }
        
        // Get quantity, treating null as 0
        $quantity = is_numeric($this->quantity) ? (float)$this->quantity : 0;
        
        // Early return if quantity is 0 to avoid division by zero
        if ($quantity <= 0) {
            return 0.00;
        }
        
        // Get fulfilled quantity, treating null or non-numeric as 0
        $fulfilledQuantity = is_numeric($this->fulfilled_quantity) ? (float)$this->fulfilled_quantity : 0;
        
        // Calculate percentage, cap at 100, and round to 2 decimal places
        return round(min(($fulfilledQuantity / $quantity) * 100, 100), 2);
    }
}