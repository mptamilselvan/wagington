<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloodTest extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'blood_tests';
    
    protected $fillable = [
        'species_id',
        'name',
        'expiry_days',
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



    public function blood_test_record()
    {
        return $this->hasMany(BloodTestRecord::class, 'blood_test_id');
    }

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

    public function vaccine_exemption()
    {
        return $this->hasOne(VaccineExemption::class, 'blood_test_id');
    }




}
