<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: variant_attribute_types
     * Purpose: Defines global variant attribute types (like Size, Color, Material) 
     * that can be reused across different products. This provides a centralized
     * way to manage variant attributes.
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('variant_attribute_types', function (Blueprint $table) {
            $table->bigIncrements('id');

            // The attribute name (e.g., "Size", "Color", "Material")
            $table->string('name', 100);

            // Slug for URL-friendly identification
            $table->string('slug', 100);

            // Type of attribute for UI rendering
            $table->enum('type', ['text', 'color', 'image', 'number'])->default('text');

            // Controls the order in displays
            $table->integer('display_order')->default(0);

            // If TRUE, shows in the filter sidebar
            $table->boolean('is_filterable')->default(true);

            // Image URL for the attribute type (optional)
            $table->string('image_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_filterable'], 'idx_attribute_types_filterable');
            $table->index(['display_order'], 'idx_attribute_types_order');
            $table->unique(['slug', 'deleted_at'], 'unq_attribute_types_slug_deleted');  // This ensures uniqueness for active records only
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_attribute_types');
    }
};