<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::deleting(function ($product) {
            try {
                if (method_exists($product, 'isForceDeleting') && $product->isForceDeleting()) {
                    // Force delete all variants (including already soft-deleted)
                    $product->variants()->withTrashed()->get()->each->forceDelete();
                } else {
                    // Soft delete variants to preserve history and support restore
                    $product->variants()->get()->each->delete();
                }
            } catch (\Exception $e) {
                // swallow
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'catalog_id',
        'category_id',
        'product_type',
        'featured',
        'shippable',
        'status',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'view_count'
    ];

    protected $casts = [
        'featured' => 'boolean',
        'shippable' => 'boolean',
        'view_count' => 'integer',
        'sort_order' => 'integer',
        'variant_attribute_type_ids' => 'array', // JSON: selected variant type IDs
    ];

    // Relationships
    public function catalog()
    {
        return $this->belongsTo(Catalog::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category_relations', 'product_id', 'category_id')
                    ->withPivot('is_primary', 'display_order')
                    ->withTimestamps()
                    ->orderBy('product_category_relations.display_order');
    }

    public function primaryCategory()
    {
        return $this->belongsToMany(Category::class, 'product_category_relations', 'product_id', 'category_id')
                    ->withPivot('is_primary', 'display_order')
                    ->wherePivot('is_primary', true);
    }



    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function mediaAssets()
    {
        return $this->hasMany(MediaAsset::class, 'product_id');
    }

    public function generalImages()
    {
        return $this->hasMany(MediaAsset::class, 'product_id')->general()->images()->ordered();
    }

    public function tags()
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag_relations', 'product_id', 'tag_id');
    }

    public function addons()
    {
        return $this->belongsToMany(Product::class, 'product_addons', 'product_id', 'addon_id')
                    ->withPivot('is_required', 'display_order')
                    ->withTimestamps();
    }

    public function parentProducts()
    {
        return $this->belongsToMany(Product::class, 'product_addons', 'addon_id', 'product_id')
                    ->withPivot('is_required', 'display_order')
                    ->withTimestamps();
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('product_type', $type);
    }

    public function scopeByCategoryIdColumn($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->whereHas('categories', function($q) use ($categoryId) {
            $q->where('category_id', $categoryId);
        });
    }

    public function scopeByCatalog($query, $catalogId)
    {
        return $query->where('catalog_id', $catalogId);
    }

    public function scopeShippable($query)
    {
        return $query->where('shippable', true);
    }

    public function scopeNotShippable($query)
    {
        return $query->where('shippable', false);
    }

    // Accessors & Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = $this->generateUniqueSlug($value);
        }
    }

    // Helper methods
    public function generateUniqueSlug($name)
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getPrimaryImage()
    {
        // For variant products, try to get general primary image first
        if ($this->product_type === 'variant') {
            $generalPrimary = $this->generalImages()->primary()->first();
            if ($generalPrimary) {
                return $generalPrimary;
            }
            
            // If no general primary image, look for the primary variant's primary image
            $primaryVariant = $this->variants()->where('is_primary', true)->first();
            if ($primaryVariant) {
                $primaryImage = $primaryVariant->getPrimaryImage();
                if ($primaryImage) {
                    return $primaryImage;
                }
            }
            
            // If still no primary image, get the first variant with a primary media asset
            $variant = $this->variants()
                        ->whereHas('mediaAssets', function ($query) {
                            $query->where('is_primary', true);
                        })
                        ->with(['mediaAssets' => function ($query) {
                            $query->where('is_primary', true);
                        }])
                        ->first();
            
            if ($variant) {
                return $variant->mediaAssets->first();
            }
            
            // No primary image found for variant product
            return null;
        }

        // For non-variant products or fallback
        $variant = $this->variants()
                    ->whereHas('mediaAssets', function ($query) {
                        $query->where('is_primary', true);
                    })
                    ->with(['mediaAssets' => function ($query) {
                        $query->where('is_primary', true);
                    }])
                    ->first();

        return $variant?->mediaAssets->first();
    }
    public function getStockQuantity()
    {
        return $this->variants()->sum('stock_quantity');
    }

    public function getMinPrice()
    {
        return $this->variants()->min('selling_price');
    }

    public function getMaxPrice()
    {
        return $this->variants()->max('selling_price');
    }

    public function isInStock()
    {
        return $this->variants()->where('stock_quantity', '>', 0)->exists();
    }

    public function hasVariants()
    {
        return $this->product_type === 'variant' && $this->variants()->count() > 1;
    }

    public function isAddon()
    {
        return $this->product_type === 'addon';
    }

    public function isRegular()
    {
        return $this->product_type === 'regular';
    }

    public function isShippable()
    {
        return $this->shippable === true;
    }
}