<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeakSeason extends Model
{
    protected $fillable = [
        'title',
        'price_variation',
        'start_date',
        'end_date',
        'description',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
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
