<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Table: categories
     * Purpose: Organizes products into a hierarchical structure (e.g., Dogs > Food > Dry Food).
     * This helps customers browse products and improves SEO through better site structure.
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->bigIncrements('id');

            // The name that appears in navigation (e.g., "Dog Food")
            $table->string('name', 100);

            // URL-friendly version (e.g., "dog-food")
            $table->string('slug', 100);

            // Links to parent category for subcategories
            $table->unsignedBigInteger('parent_id')->nullable();

            // Detailed category description for SEO
            $table->text('description')->nullable();

            // Category banner image URL
            $table->string('image_url', 500)->nullable();

            // Controls sort order in navigation
            $table->integer('display_order')->default(0);

            // Active categories are visible on site
            $table->enum('status', ['active', 'inactive'])->default('active');

            // SEO title tag (max 60 chars recommended)
            $table->string('meta_title', 60)->nullable();

            // SEO meta description (max 160 chars)
            $table->string('meta_description', 160)->nullable();

            // Main keywords to target for this category
            $table->string('focus_keywords', 255)->nullable();

            // Additional keywords for SEO
            $table->string('meta_keywords', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Self-referencing FK for hierarchical categories
            $table->foreign('parent_id')->references('id')->on('categories');

            // Indexes for common queries
            $table->index(['parent_id', 'display_order'], 'idx_categories_parent_order');
            $table->index(['status'], 'idx_categories_status');
            $table->unique(['slug', 'deleted_at'], 'unq_categories_slug_deleted');  // This ensures uniqueness for active records only
        });

        // Partial unique index on slug where deleted_at IS NULL (PostgreSQL)
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS unq_categories_slug ON categories (slug) WHERE deleted_at IS NULL;");
    }

    public function down(): void
    {
        // Drop partial index explicitly
        DB::statement('DROP INDEX IF EXISTS unq_categories_slug');
        Schema::dropIfExists('categories');
    }
};