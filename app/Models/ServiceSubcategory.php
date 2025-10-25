<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceSubcategory extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'category_id',
        'description',
        'image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'focus_keywords',
        'created_by',
        'updated_by',
    ];

    public function service_category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }
}
