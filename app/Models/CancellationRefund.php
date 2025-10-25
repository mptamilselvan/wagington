<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancellationRefund extends Model
{
    protected $fillable = [
        'type',
        'value',       
        'created_by',
        'updated_by'
    ];
}
