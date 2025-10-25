<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class VaccineExemption extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'species_id',
        'blood_test_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'blood_test_id' => 'array',
    ];

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

    // Accessor to get blood test names
    public function getBloodTestNamesAttribute()
    {
        if (empty($this->blood_test_id)) {
            return [];
        }

        return BloodTest::whereIn('id', $this->blood_test_id)
                        ->pluck('name')
                        ->toArray();
    }

    // If you want comma-separated string
    public function getBloodTestNamesStringAttribute()
    {
        return implode(', ', $this->blood_test_names);
    }

}
