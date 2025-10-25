<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id','addon_product_id','addon_variant_id','addon_name','addon_variant_display_name','addon_sku','was_required','quantity','reserved_quantity','fulfilled_quantity','unit_price','total_price'
    ];

    protected $casts = [
        'was_required' => 'boolean',
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'fulfilled_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}