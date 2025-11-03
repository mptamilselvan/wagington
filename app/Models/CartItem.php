<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Room\RoomModel;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','catalog_id','product_id','variant_id','quantity','expires_at','availability_status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'availability_status' => 'string',
        'expires_at' => 'datetime',
    ];

    public function variant(){ return $this->belongsTo(ProductVariant::class, 'variant_id'); }
    public function product(){ return $this->belongsTo(Product::class, 'product_id'); }
    public function addons(){ return $this->hasMany(CartAddon::class, 'cart_item_id'); }

    public function catalog(){ return $this->belongsTo(Catalog::class, 'catalog_id'); }
    public function room(){ return $this->belongsTo(RoomModel::class, 'product_id'); }
    public function roomDetails(){ return $this->hasOne(CartRoomDetail::class, 'cart_item_id'); }
}