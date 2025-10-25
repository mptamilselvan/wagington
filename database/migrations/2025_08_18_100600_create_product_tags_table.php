<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: product_tags
     * Purpose: Flexible labeling system for products that enables advanced filtering
     * and marketing capabilities beyond the category structure.
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('product_tags', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Display name of the tag
            $table->string('name', 100)->unique();

            // URL-friendly version of the name
            $table->string('slug', 100);

            // Color for tag display
            $table->string('color', 7)->default('#007bff');

            // Optional description
            $table->text('description')->nullable();

            // Whether the tag is active
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Index for filtering by active status
            $table->index(['is_active'], 'idx_tags_active');
            $table->unique(['slug', 'deleted_at'], 'unq_product_tags_slug_deleted');  // This ensures uniqueness for active records only

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tags');
    }
};