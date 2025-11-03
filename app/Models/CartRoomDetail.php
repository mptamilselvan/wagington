<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Room\RoomModel;
use App\Models\Room\RoomTypeModel;
use App\Models\User;
use App\Models\PetSize;
use App\Models\ServiceAddon;
use App\Models\CartAddon;

class CartRoomDetail extends Model
{
    use HasFactory;

    protected $table = 'cart_room_details';

    protected $fillable = [
        'cart_item_id',
        'room_id',
        'room_type_id',
        'customer_id',
        'pets_reserved',
        'service_addons',
        'check_in_date',
        'check_out_date',
        'no_of_days',
        'room_price',
        'addons_price',
        'service_charge',
        'pet_quantity',
        'total_price',
    ];

    protected $casts = [
        'pets_reserved' => 'array',
        'service_addons' => 'array',
        'check_in_date' => 'date',
        'check_out_date' => 'date',
    ];

    public function room()
    {
        return $this->belongsTo(RoomModel::class, 'room_id');
    }
    public function roomType()
    {
        return $this->belongsTo(RoomTypeModel::class, 'room_type_id');
    }
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    public function petSizes()
    {
        return $this->hasMany(PetSize::class, 'cart_room_detail_id');
    }
    public function serviceAddons()
    {
        return $this->hasMany(ServiceAddon::class, 'cart_room_detail_id');
    }
    public function addons()
    {
        return $this->hasMany(CartAddon::class, 'cart_room_detail_id');
    }
}


