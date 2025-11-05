<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceType extends Model
{
    use HasFactory, SoftDeletes;

    // protected $table = 'service_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'focus_keywords',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function services()
    {
        return $this->hasMany(Service::class, 'service_type_id');
    }
}
