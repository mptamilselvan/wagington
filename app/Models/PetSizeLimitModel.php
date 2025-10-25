<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Species;
use App\Models\RoomTypeModel;
use App\Models\RoomPriceOptionModel;

class PetSizeLimitModel extends Model
{
    use HasFactory;
    protected $table = "pet_size_limits";
    protected $fillable = [
        'id',
        'room_type_id',
        'allowed_pet_size',
        'current_room_capacity',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // Relationships
    public function roomType()
    {
        return $this->belongsTo(RoomTypeModel::class);
    }
    public function roomPriceOptions()
    {
        return $this->hasOne(RoomPriceOptionModel::class,'room_type_id');
    }


}