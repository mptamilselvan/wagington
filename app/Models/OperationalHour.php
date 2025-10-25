<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationalHour extends Model
{
    protected $fillable = [
        'day',
        'start_time',
        'end_time',
        'created_by'
    ];
}
