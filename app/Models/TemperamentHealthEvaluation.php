<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemperamentHealthEvaluation extends Model
{
    protected $fillable = [
        'pet_id',
        'administer_name',
        'date',
        'notes',
        'behaviour',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * The pet that this evaluation belongs to.
     */
    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }
}
