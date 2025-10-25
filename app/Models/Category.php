<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::deleting(function ($category) {
            // Soft delete: optionally remove later; for force delete, cleanup now
            try {
                if (method_exists($category, 'isForceDeleting') && $category->isForceDeleting()) {
                    $rawImage = $category->getRawOriginal('image_url');
                    if (!empty($rawImage)) {
                        \App\Services\ImageService::deleteImage($rawImage);
                    }
                }
            } catch (\Exception $e) {}
        });

        static::forceDeleted(function ($category) {
            try {
                $rawImage = $category->getRawOriginal('image_url');
                if (!empty($rawImage)) {
                    \App\Services\ImageService::deleteImage($rawImage);
                }
            } catch (\Exception $e) {}
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'description',
        'image_url',
        'display_order',
        'status',
        'meta_title',
        'meta_description',
        'focus_keywords',
        'meta_keywords',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'status' => 'string',
    ];

    /**
     * Get the parent category.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('display_order');
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Scope to get only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get only root categories (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the full path of the category (for breadcrumbs)
     */
    public function getFullPathAttribute()
    {
        $path = collect([$this->name]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }
        
        return $path->implode(' > ');
    }

    /**
     * Check if category has children
     */
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    /**
     * Get the products related to this category
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category_relations', 'category_id', 'product_id')
                    ->withPivot('is_primary', 'display_order')
                    ->withTimestamps()
                    ->orderBy('product_category_relations.display_order');
    }

    /**
     * Get the image URL with fallback
     */
    public function getImageUrlAttribute($value)
    {
        // Return the stored full URL directly, or fallback if empty
        return $value ?: asset('images/default-avatar.svg');
    }
}