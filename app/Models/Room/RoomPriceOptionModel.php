<?php

namespace App\Models\Room;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Room\RoomTypeModel;
use App\Models\Size;

class RoomPriceOptionModel extends Model
{
    use HasFactory;
    protected $table = "room_price_options";
    protected $fillable = [
        'id',
        'room_type_id',
        'label',
        'no_of_days',
        'price',
        'pet_size_id',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'room_type_id' => 'integer',
        'label' => 'string',
        'no_of_days' => 'integer',
        'price' => 'decimal:2',
        'status' => 'string',
        'pet_size_id' => 'integer',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomTypeModel::class,'room_type_id');
    }

    public function petSize()
    {
        return $this->belongsTo(Size::class,'pet_size_id');
    }

}