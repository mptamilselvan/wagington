<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\RoomTypeModel;

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
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomTypeModel::class,'room_type_id');
    }

}