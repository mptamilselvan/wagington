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

    public function getFulfillmentProgress()
    {
        if ($this->fulfillment_status === 'handed_over') {
            return 100; // Digital items are fully fulfilled when handed over
        }
        
        if ($this->quantity == 0) {
            return 0;
        }
        
        return round(($this->fulfilled_quantity / $this->quantity) * 100, 2);
    }
}