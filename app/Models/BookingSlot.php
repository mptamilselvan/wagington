<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSlot extends Model
{
    protected $fillable = [
        'day',
        'start_time',
        'end_time',
        'created_by',
        'updated_by'
    ];
}
