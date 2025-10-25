<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RevaluationWorkflow extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'species_id',
        'age_threshold_months',
        'is_required',
        'created_by',
        'updated_by'
    ];

    public function species()
    {
        return $this->belongsTo(Species::class, 'species_id');
    }
}
