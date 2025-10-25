<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token','product_id','variant_id','quantity','expires_at','availability_status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'availability_status' => 'string',
        'expires_at' => 'datetime',
    ];

    public function variant(){ return $this->belongsTo(ProductVariant::class, 'variant_id'); }
    public function product(){ return $this->belongsTo(Product::class, 'product_id'); }
    public function addons(){ return $this->hasMany(GuestCartAddon::class, 'guest_cart_item_id'); }
}