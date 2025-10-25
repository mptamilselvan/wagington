<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingStatusSetting extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = ucfirst(strtolower($value));
    }

    public function getNameAttribute()
    {
        return ucfirst(strtolower($this->attributes['name']));
    }
}
