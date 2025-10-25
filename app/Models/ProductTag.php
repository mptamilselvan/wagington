<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = static::generateUniqueSlug($tag->name);
            } else {
                $tag->slug = static::generateUniqueSlug($tag->slug, $tag->getKey());
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') || $tag->isDirty('slug')) {
                $source = $tag->isDirty('slug') ? $tag->slug : $tag->name;
                $tag->slug = static::generateUniqueSlug($source, $tag->getKey());
            }
        });
    }


    protected static function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);
        $slug     = $baseSlug;
        $suffix   = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query, $id) => $query->whereKeyNot($id))
            ->exists()
        ) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Get the products that have this tag.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tag_relations', 'tag_id', 'product_id');
    }

    /**
     * Scope to get only active tags.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the tag's color with default.
     */
    public function getColorAttribute($value)
    {
        return $value ?: '#007bff';
    }
}