<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Size extends Model
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

    public function size_management()
    {
        return $this->hasMany(SizeManagement::class, 'size_id');
    }
}
