<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_item_id','addon_product_id','addon_variant_id','quantity','is_required',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_required' => 'boolean',
    ];

    public function cartItem(){ return $this->belongsTo(CartItem::class, 'cart_item_id'); }
    public function variant(){ return $this->belongsTo(ProductVariant::class, 'addon_variant_id'); }
    public function product(){ return $this->belongsTo(Product::class, 'addon_product_id'); }
}