<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id','product_id','variant_id','product_name','variant_display_name','sku','product_attributes','quantity','reserved_quantity','fulfilled_quantity','unit_price','total_price'
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
}