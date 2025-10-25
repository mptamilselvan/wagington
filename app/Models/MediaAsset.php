<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MediaAsset extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::deleting(function ($media) {
            // Remove from storage when record is deleted. Use path or fallback to URL.
            try {
                $target = $media->file_path ?: $media->file_url;
                \App\Services\ImageService::deleteImage($target);
            } catch (\Exception $e) {
                // swallow
            }
        });
    }

    protected $fillable = [
        'product_id',
        'variant_id',
        'scope',
        'type',
        'file_path',
        'file_url',
        'alt_text',
        'display_order',
        'is_primary',
        'file_size',
        'mime_type',
        'width',
        'height'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'display_order' => 'integer',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    // Scopes
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    public function scopeGeneral($query)
    {
        return $query->where('scope', 'general');
    }

    public function scopeVariantSpecific($query)
    {
        return $query->where('scope', 'variant');
    }

    public function scopeOptions($query)
    {
        return $query->where('scope', 'option');
    }

    // Accessors: always resolve URL from current preferred disk when file_path exists
    public function getFileUrlAttribute($value)
    {
        try {
            if (!empty($this->file_path)) {
                // Use ImageService to get the proper URL with fallbacks
                $resolvedUrl = \App\Services\ImageService::getImageUrl($this->file_path, $value);

                if (!empty($resolvedUrl)) {
                    return $resolvedUrl;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to generate image URL', [
                'media_asset_id' => $this->id,
                'file_path' => $this->file_path,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback to stored value or default image
        return $value ?: asset('images/default-image.png');
    }

    // Helper methods
    public function isImage()
    {
        return $this->type === 'image';
    }

    public function isVideo()
    {
        return $this->type === 'video';
    }

    public function getFormattedFileSize()
    {
        if (!$this->file_size) {
            return null;
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDimensions()
    {
        if (!$this->width || !$this->height) {
            return null;
        }
        
        return $this->width . ' x ' . $this->height;
    }
}