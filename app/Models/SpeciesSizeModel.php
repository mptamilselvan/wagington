<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SpeciesSizeModel extends Model
{
    use HasFactory;
    protected $table = "species_size";
    protected $fillable = [
        'id',
        'species_id',
        'size',
        'description',
        'image',
        'icon',
        'color',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

}