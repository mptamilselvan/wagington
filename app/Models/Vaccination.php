<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vaccination extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'species_id',
        'name',
        'expiry_days',
        'is_active',
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

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

    public function vaccination_record()
    {
        return $this->hasMany(VaccinationRecord::class, 'vaccination_id');
    }


}
