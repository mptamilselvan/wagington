<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicationSupplementAdminDetail extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'medication_supplement_id',
        'administer_name',
        'date',
        'time',
        'administer_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
    ];
}
