<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoolSetting extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'type',
        'allowed_pet',
        'created_by',
        'updated_by',
    ];
}
