<?php

namespace App\Models\Room;

use Illuminate\Database\Eloquent\Model;

class PeakSeasonModel extends Model
{
    protected $table = 'room_peak_season';
    protected $fillable = [
        'title',
        'peak_price_variation',
        'start_date',
        'end_date',
        'description',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'peak_price_variation' => 'decimal:2',
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
