<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: product_category_relations
     * Purpose: Establishes many-to-many relationships between products and categories,
     * allowing products to have multiple categories and categories to contain multiple products.
     * This replaces the single primary_category_id approach with a more flexible system.
     */
    public function up(): void
    {
        Schema::create('product_category_relations', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Reference to the product
            $table->unsignedBigInteger('product_id');

            // Reference to the category
            $table->unsignedBigInteger('category_id');

            // Indicates if this is the primary category for the product
            $table->boolean('is_primary')->default(false);

            // Display order for categories on product page
            $table->integer('display_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // FKs with cascade delete behavior
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');

            // Prevent duplicate relations
            $table->unique(['product_id', 'category_id'], 'unq_product_category_active')
                ->whereNull('deleted_at');
            
            // Index for finding primary category quickly
            $table->index(['product_id', 'is_primary'], 'idx_product_primary_category');
            
            // Index for category-based queries
            $table->index(['category_id', 'is_primary'], 'idx_category_products');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category_relations');
    }
};