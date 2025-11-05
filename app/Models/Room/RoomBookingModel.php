<?php

namespace App\Models\Room;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Species;
use App\Models\Room\RoomTypeModel;
use App\Models\Room\RoomPriceOptionModel;
use App\Models\Room\PetSizeLimitModel;
use App\Models\Size;
use App\Models\User;
use App\Models\Pet;

class RoomBookingModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "room_booking";
    protected $fillable = [
        'id',
        'room_id',
        'room_type_id',
        'customer_id',
        'order_id',
        'order_number',
        'room_price',
        'room_price_label',
        'pets_reserved',
        'species_id',
        'pet_quantity',
        'service_addons',
        'is_peak_season',
        'is_off_day',
        'is_weekend',
        'check_in_date',
        'check_out_date',
        'no_of_days',
        'service_charge',
        'total_price',
        'payment_status',
        'booking_status',
        'payment_method',
        'payment_reference',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'pets_reserved' => 'array',
        'service_addons' => 'array',
        'payment_status' => 'string',
        'booking_status' => 'string',
        'payment_method' => 'string',
        'payment_reference' => 'string',
        'order_id' => 'integer',
        'order_number' => 'string',
    ];

    // Relationships
    public function roomType()
    {
        return $this->belongsTo(RoomTypeModel::class);
    }
    public function room()
    {
        return $this->belongsTo(RoomModel::class);
    }
    public function customer()
    {
        return $this->belongsTo(User::class);
    }
    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }


}