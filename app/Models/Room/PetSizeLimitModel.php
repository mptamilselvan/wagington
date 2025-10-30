<?php

namespace App\Models\Room;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Species;
use App\Models\Room\RoomTypeModel;
use App\Models\Size;

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
        return $this->belongsTo(RoomTypeModel::class, 'room_type_id');
    }
    public function petSize()
    {
        return $this->belongsTo(Size::class,'pet_size_id');
    }

}