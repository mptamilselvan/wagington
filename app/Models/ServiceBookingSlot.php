<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceBookingSlot extends Model
{
    use HasFactory;

    protected $table = 'services_booking_slots';

    protected $fillable = [
        'service_id',
        'day',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    // 🔗 Relationships
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
