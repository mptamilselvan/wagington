<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePricingAttributes extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];
}
