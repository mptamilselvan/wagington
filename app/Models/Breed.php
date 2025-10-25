<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Pet;

class Breed extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'species_id',       
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
        // other fields...
    ];

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }
    public function pets()
    {
        return $this->hasMany(Pet::class);
    }
}
