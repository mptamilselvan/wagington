<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PetTag extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'species_id',
        'tag',
        'from_age',
        'to_age',
        'created_by',
        'updated_by'
    ];

    public function setTagAttribute($value)
    {
        $this->attributes['tag'] = ucfirst(strtolower($value));
    }

    public function getTagAttribute()
    {
        return ucfirst(strtolower($this->attributes['tag']));
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

}
