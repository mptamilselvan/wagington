<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CancelSetting extends Model
{
    use HasFactory;
    protected $table = "cancellation_settings";
    protected $fillable = [
        'id',
        'before_6_hour_percentage',
        'before_24_hour_percentage',
        'before_72_hour_percentage',
        'admin_cancel_percentage',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'before_6_hour_percentage' => 'integer',
        'before_24_hour_percentage' => 'integer',
        'before_72_hour_percentage' => 'integer',
        'admin_cancel_percentage' => 'integer',
        'status' => 'string',
    ];

}