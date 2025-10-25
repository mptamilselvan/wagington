<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Table: products
     * Purpose: Stores the main product information that doesn't vary between different versions
     * (like size or color). Each product can have multiple variants.
     * Product types: regular (standalone), variant (has variations), addon (add-on product).
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('catalog_id');

            // Product display name (e.g., "Premium Dog Food")
            $table->string('name', 255);

            // URL-friendly name (e.g., "premium-dog-food")
            $table->string('slug', 255);

            // Brief description for product listings
            $table->string('short_description', 160)->nullable();

            // Full product details with HTML formatting
            $table->longText('description')->nullable();

            // Primary category (parent)
            $table->unsignedBigInteger('category_id');

            // Product type classification
            $table->enum('product_type', ['regular', 'variant', 'addon'])->default('regular');

            // Selected variant attribute type IDs (development: inline JSON instead of separate table)
            $table->json('variant_attribute_type_ids')->nullable();

            // Featured products can be highlighted on the homepage
            $table->boolean('featured')->default(false);
            $table->boolean('shippable')->default(false);

            // Controls product visibility
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            // Manual sort order within categories
            $table->integer('sort_order')->default(0);

            // SEO title (overrides default if set)
            $table->string('meta_title', 60)->nullable();

            // SEO meta description
            $table->string('meta_description', 160)->nullable();

            // Keywords for search engines
            $table->text('meta_keywords')->nullable();

            // Tracks how many times product was viewed
            $table->integer('view_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('catalog_id')->references('id')->on('catalogs');
            $table->foreign('category_id')->references('id')->on('categories');

            // Indexes
            $table->index(['category_id'], 'idx_products_category');
            $table->index(['status', 'featured'], 'idx_products_status_featured');
            $table->index(['product_type'], 'idx_products_type');
            $table->index(['product_type', 'status'], 'idx_products_type_status');
            $table->index(['deleted_at'], 'idx_products_deleted');
            $table->index('catalog_id', 'idx_products_catalog');
            $table->index('shippable', 'idx_products_shippable');
            // This ensures uniqueness for active records only
            $table->unique(['slug', 'deleted_at'], 'unq_products_slug_deleted');
        });

        // Partial unique index on slug where deleted_at IS NULL
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS unq_products_slug ON products (slug) WHERE deleted_at IS NULL;");

        // Full-text search index : GIN on to_tsvector over name + short_description
        DB::statement("CREATE INDEX IF NOT EXISTS idx_products_search ON products USING GIN (to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(short_description, '')));");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_products_search');
        DB::statement('DROP INDEX IF EXISTS unq_products_slug');
        Schema::dropIfExists('products');
    }
};