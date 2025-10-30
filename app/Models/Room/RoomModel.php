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

class RoomModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "rooms";
    protected $fillable = [
        'id',
        'name',
        'room_type_id',
        'cctv_stream',
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
        return $this->hasMany(RoomPriceOptionModel::class,'room_type_id');
    }   
    public function petsizeLimits()
    {
        return $this->hasMany(PetSizeLimitModel::class,'room_type_id');
    }


}