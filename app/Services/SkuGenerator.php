<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ProductVariant;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SkuGenerator
{
    // Compose base parts like "SP-FD-DG-PR" (PR = first 2 letters of product name, optional)
    public function makeBasePrefix(string $productType, ?Category $category, ?string $productName): string
    {
        $ptCode = $this->productTypeCode($productType);
        $catCode = $this->categoryCode($category);

        $parts = [$ptCode, $catCode];

        if (Config::get('sku.include_name_prefix') && $productName) {
            $len = max(1, (int) Config::get('sku.name_prefix_length', 2));
            $nameCode = Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName), 0, $len));
            if ($nameCode !== '') {
                $parts[] = $nameCode;
            }
        }

        return implode('-', $parts);
    }

    // Find next 4-digit number for a given base prefix, e.g., "SP-FD-DG-PR" -> "0001"
    public function nextSequence(string $basePrefix): string
    {
        // Search latest SKU that begins with basePrefix-
       return DB::transaction(function () use ($basePrefix) {
            $latest = ProductVariant::where('sku', 'like', $basePrefix . '-%')
                ->orderBy('sku', 'desc')
                ->lockForUpdate()
                ->value('sku');

            $next = 1;
            if ($latest && preg_match('/-(\d{4})(?:-|$)/', $latest, $m)) {
                $next = (int) $m[1] + 1;
            }

            return str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });

    }

    // Full SKU for non-variant products (regular/addon)
    public function makeForProduct(string $productType, int $categoryId, string $productName): string
    {
        $category = Category::find($categoryId);
        $base     = $this->makeBasePrefix($productType, $category, $productName);
        $seq      = $this->nextSequence($base);
        return $base . '-' . $seq;
    }

    // Full SKU for variant (base with number + attribute suffix)
    public function makeForVariant(string $productType, int $categoryId, string $productName, array $attributes): string
    {
        $category = Category::find($categoryId);
        $base     = $this->makeBasePrefix($productType, $category, $productName);
        $seq      = $this->nextSequence($base);
        $suffix   = $this->variantSuffix($attributes);
        return $base . '-' . $seq . ($suffix ? '-' . $suffix : '');
    }

    // Build base-with-number once to reuse for multiple variants
    public function makeBaseWithNumber(string $productType, int $categoryId, string $productName): string
    {
        $category = Category::find($categoryId);
        $base     = $this->makeBasePrefix($productType, $category, $productName);
        $seq      = $this->nextSequence($base);
        return $base . '-' . $seq;
    }

    public function makeVariantFromBase(string $baseWithNumber, array $attributes): string
    {
        $suffix = $this->variantSuffix($attributes);
        return $baseWithNumber . ($suffix ? '-' . $suffix : '');
    }

    // Helpers

    protected function productTypeCode(string $productType): string
    {
        $map = Config::get('sku.product_type_codes', []);
        return $map[$productType] ?? Str::upper(substr($productType, 0, 2));
    }

    protected function categoryCode(?Category $category): string
    {
        if (!$category) return 'GN';
        $map = Config::get('sku.category_codes', []);
        $slug = Str::slug($category->slug ?: $category->name);
        if (isset($map[$slug])) return $map[$slug];
        return Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string)($category->name ?? 'GN')), 0, 3));
    }

    protected function variantSuffix(array $attributes): string
    {
        if (empty($attributes)) return '';
        $codes = [];

        ksort($attributes);
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === '') continue;
            $codes[] = $this->variantAttrCode($name, (string)$value);
        }

        return implode('-', array_filter($codes, fn ($code) => $code !== ''));
    }

    protected function variantAttrCode(string $attributeName, string $attributeValue): string
    {
        $maps = Config::get('sku.variant_attribute_codes', []);
        $name = Str::lower($attributeName);
        $valU = Str::upper($attributeValue);

        if (isset($maps[$name][$valU])) {
            return $maps[$name][$valU];
        }

        // Fallback: first 3 alphanumerics
        return Str::upper(substr(preg_replace('/[^A-Za-z0-9]/', '', $attributeValue), 0, 3));
    }
}