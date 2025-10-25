<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class MedicationSupplement extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'pet_id',
        'customer_id',
        'type',
        'name',
        'dosage',
        'notes',
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

    public function admin_detail()
    {
        return $this->hasMany(MedicationSupplementAdminDetail::class, 'medication_supplement_id','id');
    }

    // Accessor for formatted created_at
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y h:i:s A');
    }
}
