<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VariantAttributeType extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::deleting(function (self $variantType) {
            // When a variant type is deleted, also remove its image from storage.
            if ($imageUrl = $variantType->getRawOriginal('image_url')) {
                try {
                    \App\Services\ImageService::deleteImage($imageUrl);
                } catch (\Exception $e) {
                    // Log or ignore the error
                }
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'type',
        'display_order',
        'is_filterable',
        'image_url',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'is_filterable' => 'boolean',
    ];

    /**
     * Get the attribute values for this type.
     */
    public function values()
    {
        return $this->hasMany(VariantAttributeValue::class, 'attribute_type_id')->orderBy('sort_order');
    }

    /**
     * Get the image URL with fallback
     */
    public function getImageUrlAttribute($value)
    {
        // Return the stored full URL directly, or fallback if empty
        return $value ?: asset('images/default-avatar.svg');
    }

    /**
     * Check if attribute type has values
     */
    public function hasValues()
    {
        return $this->values()->count() > 0;
    }

    /**
     * Auto-generate slug when name is set
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }
}