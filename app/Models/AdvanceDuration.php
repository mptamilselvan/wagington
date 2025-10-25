<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvanceDuration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'advance_days',
        'advance_hours',
        'updated_by',
        'created_by'

    ];
}
