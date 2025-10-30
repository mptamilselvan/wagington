<?php

namespace App\Models\Room;

use Illuminate\Database\Eloquent\Model;

class OffDayModel extends Model
{
    protected $table = 'room_off_days';
    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'reason',
        'off_day_price_variation',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'off_day_price_variation' => 'decimal:2',
    ];

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = ucfirst(strtolower($value));
    }

    public function getTitleAttribute()
    {
        return ucfirst(strtolower($this->attributes['title']));
    }
}
