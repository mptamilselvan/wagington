<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class BloodTestRecord extends Model
{
    use SoftDeletes;

    protected $table = 'blood_test_records';

    protected $fillable = [
        'pet_id',
        'customer_id',
        'blood_test_id',
        'status',
        'date',
        'document',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function getStatusAttribute()
    {
        return ucfirst(strtolower($this->attributes['status']));
    }

    public function blood_test()
    {
        return $this->belongsTo(BloodTest::class, 'blood_test_id');
    }

    // Accessor for formatted created_at
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y h:i:s A');
    }
}
