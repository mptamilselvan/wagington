<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Species extends Model
{
    use HasFactory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'species';
    protected $fillable = [
    'name',
    'description',
    'image_url',
    'conservation_status',
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
        return $this->hasMany(Pet::class, 'species_id','id');
    }

    public function breed()
    {
        return $this->hasMany(Breed::class, 'species_id','id');
    }

    public function vaccination()
    {
        return $this->hasMany(Vaccination::class, 'species_id','id');
    }

    public function blood_test()
    {
        return $this->hasMany(BloodTest::class, 'species_id','id');
    }

    public function pet_tag()
    {
        return $this->hasMany(PetTag::class, 'species_id','id');
    }

    public function vaccine_exemption()
    {
        return $this->hasMany(VaccineExemption::class, 'species_id','id');
    }

    public function revaluation_workflow()
    {
        return $this->hasMany(RevaluationWorkflow::class, 'species_id','id');
    }
}
