<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'variant_attributes', // JSON attribute map (renamed from 'attributes')
        'sku',
        'barcode',
        'is_primary',
        'cost_price',
        'selling_price',
        'compare_price',
        'stock_quantity',
        'min_quantity_alert',
        'max_quantity_per_order',
        'track_inventory',
        'allow_backorders',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'status'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_quantity_alert' => 'integer',
        'max_quantity_per_order' => 'integer',
        'track_inventory' => 'boolean',
        'allow_backorders' => 'boolean',
        'sold_stock' => 'integer',
        'weight_kg' => 'decimal:3',
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'variant_attributes' => 'array', // JSON: attribute map for this variant
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function mediaAssets()
    {
        return $this->hasMany(MediaAsset::class, 'variant_id');
    }

    public function variantImages()
    {
        return $this->hasMany(MediaAsset::class, 'variant_id')->variantSpecific()->images()->ordered();
    }

    public function optionImages()
    {
        return $this->hasMany(MediaAsset::class, 'variant_id')->options()->images()->ordered();
    }

    protected static function booted()
    {
        static::deleting(function ($variant) {
            // When variant is deleted (soft or hard), delete associated media records (triggers storage delete via MediaAsset model)
            try {
                $variant->mediaAssets()->get()->each->delete();
            } catch (\Exception $e) {
                // Log and rethrow so the deletion is aborted and the error surfaces
                Log::error('Failed to delete media assets for product variant', [
                    'variant_id' => $variant->id ?? null,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class, 'variant_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_inventory', false)
              ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    // Helper methods
    public function getPrimaryImage()
    {
        // First try option primary image (this is the image uploaded in the variant options table)
        $optionPrimary = $this->optionImages()->primary()->first();
        if ($optionPrimary) {
            return $optionPrimary;
        }

        // Then try variant-specific primary image
        $variantPrimary = $this->variantImages()->primary()->first();
        if ($variantPrimary) {
            return $variantPrimary;
        }

        // Fallback to any primary image attached to this variant
        $anyPrimary = $this->mediaAssets()->where('is_primary', true)->first();
        if ($anyPrimary) {
            return $anyPrimary;
        }

        // If no explicit primary is set on variant/option, pick the first available
        // image based on display_order (prefer option image, then variant image).
        $firstOptionImage = $this->optionImages()->first();
        if ($firstOptionImage) {
            return $firstOptionImage;
        }
        $firstVariantImage = $this->variantImages()->first();
        if ($firstVariantImage) {
            return $firstVariantImage;
        }

        // Final fallback: use product-level primary image for variant-type products
        // This covers cases where a non-primary variant has no own primary image.
        if ($this->product && $this->product->product_type === 'variant') {
            return $this->product->getPrimaryImage();
        }

        return null;
    }

    public function getAllImages()
    {
        return $this->mediaAssets()->where('type', 'image')->orderBy('display_order')->get();
    }

    public function getDisplayImages()
    {
        // For regular/addon products, get all images from variant media assets
        if ($this->product && ($this->product->product_type === 'regular' || $this->product->product_type === 'addon')) {
            return $this->mediaAssets()->images()->ordered()->get();
        }
        
        // For variant products, get general images from product + variant-specific images
        $generalImages = $this->product->generalImages;
        $variantImages = $this->variantImages;
        $optionImages = $this->optionImages;

        // If this is the primary variant, prioritize its images
        if ($this->is_primary) {
            // Get the primary image for this variant (should be from option images)
            $primaryImage = $this->getPrimaryImage();
            
            // Combine all images
            $allImages = $generalImages->concat($variantImages)->concat($optionImages)->sortBy('display_order');
            
            // If we have a primary image, make sure it's first
            if ($primaryImage) {
                // Remove the primary image from the collection if it exists
                $allImages = $allImages->filter(function ($image) use ($primaryImage) {
                    return $image->id !== $primaryImage->id;
                });
                
                // Prepend the primary image to the beginning
                $allImages = collect([$primaryImage])->merge($allImages);
            }
            
            return $allImages;
        }
        
        // For non-primary variants, combine and sort by display order
        return $generalImages->concat($variantImages)->concat($optionImages)->sortBy('display_order');
    }

    public function isInStock()
    {
        if (!$this->track_inventory) {
            return true;
        }
        return $this->stock_quantity > 0;
    }

    public function isLowStock()
    {
        if (!$this->track_inventory) {
            return false;
        }
        return $this->stock_quantity <= $this->min_quantity_alert;
    }

    public function canBackorder()
    {
        return $this->allow_backorders && !$this->isInStock();
    }

    public function getDiscountPercentage()
    {
        if (!$this->compare_price || $this->compare_price <= $this->selling_price) {
            return 0;
        }
        
        return round((($this->compare_price - $this->selling_price) / $this->compare_price) * 100);
    }

    public function getFormattedPrice()
    {
        return '$' . number_format($this->selling_price, 2);
    }

    public function getFormattedComparePrice()
    {
        if (!$this->compare_price) {
            return null;
        }
        return '$' . number_format($this->compare_price, 2);
    }

    public function getStockStatus()
    {
        if (!$this->track_inventory) {
            return 'Available';
        }

        if ($this->stock_quantity <= 0) {
            return $this->allow_backorders ? 'Backorder' : 'Out of Stock';
        }

        if ($this->isLowStock()) {
            return 'Low Stock';
        }

        return 'Available';
    }

    // New helpers for availability based on reserved stock
    public function availableStock(): int
    {
        if (!$this->track_inventory) return PHP_INT_MAX;
        $reserved = (int)($this->reserved_stock ?? 0);
        $stock = (int)($this->stock_quantity ?? 0);
        return max(0, $stock - $reserved);
    }

    public function availabilityLabel(): string
    {
        if (!$this->track_inventory) return 'In Stock';
        $avail = $this->availableStock();
        if ($avail > 0) return $avail <= $this->min_quantity_alert ? 'Low Stock' : 'In Stock';
        return $this->allow_backorders ? 'Available on Backorder' : 'Out of Stock';
    }
}