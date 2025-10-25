<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: media_assets
     * Purpose: Stores all product images and videos, with support for multiple media items
     * per product variant and ordering of media in galleries.
     * Supports both product-level (general) and variant-level (specific) images.
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->bigIncrements('id');

            // The product this media belongs to (for general images shared across variants)
            $table->unsignedBigInteger('product_id')->nullable();

            // The variant this media belongs to (for variant-specific images)
            $table->unsignedBigInteger('variant_id')->nullable();

            // Image scope: 'general' for product-level, 'variant' for variant-specific, 'option' for variant options
            $table->enum('scope', ['general', 'variant', 'option'])->default('general');

            // Type of media
            $table->enum('type', ['image', 'video'])->default('image');

            // Server path to the file
            $table->string('file_path', 500)->nullable();

            // Public URL to access the media
            $table->string('file_url', 500);

            // Accessibility text for screen readers
            $table->string('alt_text', 255)->nullable();

            // Order in which to display in galleries
            $table->integer('display_order')->default(0);

            // Whether this is the main image for the variant/product
            $table->boolean('is_primary')->default(false);

            // File size in bytes
            $table->integer('file_size')->nullable();

            // MIME type (e.g., "image/jpeg")
            $table->string('mime_type', 100)->nullable();

            // Image dimensions in pixels
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FK constraints - at least one of product_id or variant_id must be set
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('cascade');

            // Indexes
            $table->index(['product_id', 'scope', 'display_order'], 'idx_media_product_scope_order');
            $table->index(['variant_id', 'scope', 'display_order'], 'idx_media_variant_scope_order');
            $table->index(['is_primary'], 'idx_media_primary');
            $table->index(['scope'], 'idx_media_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};