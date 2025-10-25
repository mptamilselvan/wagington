<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class DietaryPreferences extends Model
{
    use SoftDeletes;

    protected $table = 'dietary_preferences';
    
    protected $fillable = [
        'pet_id',
        'customer_id',
        'feed_time',
        'allergies',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    // Accessor for formatted created_at
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y h:i:s A');
    }
}
