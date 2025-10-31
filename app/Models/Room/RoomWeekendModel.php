<?php

namespace App\Models\Room;

use Illuminate\Database\Eloquent\Model;

class RoomWeekendModel extends Model
{
    protected $table = 'room_weekend';
    protected $fillable = [
        'title',
        'weekend_price_variation',
        'description',
        'created_by'
    ];

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = ucfirst(strtolower($value));
    }
}
