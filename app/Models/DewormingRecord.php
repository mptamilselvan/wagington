<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class DewormingRecord extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'pet_id',
        'customer_id',
        'brand_name',
        'date',
        'document',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function setBrandNameAttribute($value)
    {
        $this->attributes['brand_name'] = ucfirst(strtolower($value));
    }

    public function getBrandNameAttribute()
    {
        return ucfirst(strtolower($this->attributes['brand_name']));
    }

    protected $casts = [
        'date' => 'date',
    ];

    // Accessor for formatted created_at
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y h:i:s A');
    }
}
