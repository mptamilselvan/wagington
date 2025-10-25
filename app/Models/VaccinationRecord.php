<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class VaccinationRecord extends Model
{
    use SoftDeletes;
    protected $table = 'vaccination_records';

    protected $fillable = [
        'pet_id',
        'customer_id',
        'vaccination_id',
        'date',
        'notes',
        'cannot_vaccinate',
        'created_by',
        'updated_by',
        'document'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function vaccination()
    {
        return $this->belongsTo(Vaccination::class, 'vaccination_id');
    }

    // Accessor for formatted created_at
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y h:i:s A');
    }
}
