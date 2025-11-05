<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    use SoftDeletes;
    
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

    public function service_subcategory()
    {
        return $this->hasMany(ServiceSubcategory::class, 'category_id','id');
    }
}
