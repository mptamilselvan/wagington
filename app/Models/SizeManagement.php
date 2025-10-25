<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SizeManagement extends Model
{
    use SoftDeletes;

    protected $table = 'size_management';

    protected $fillable = [
        'pet_id',
        'customer_id',
        'size_id',
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

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }
}
