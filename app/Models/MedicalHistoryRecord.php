<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class MedicalHistoryRecord extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'pet_id',
        'customer_id',
        'name',
        'document',
        'notes',
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

    // Accessor for formatted created_at
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y h:i:s A');
    }
}
