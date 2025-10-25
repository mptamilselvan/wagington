<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: product_addons
     * Purpose: Establishes relationships between main products and their add-on products,
     * allowing products to have optional or required add-ons (e.g., accessories, services).
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('product_addons', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Reference to the main product
            $table->unsignedBigInteger('product_id');

            // Reference to the add-on product
            $table->unsignedBigInteger('addon_id');

            // Whether this add-on is mandatory for the main product
            $table->boolean('is_required')->default(false);

            // Controls the order in which add-ons are displayed
            $table->integer('display_order')->default(0);

            $table->timestamps();

            // Add soft deletes and proper constraints
            $table->softDeletes();
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('addon_id')->references('id')->on('products');
            
            // Prevent duplicate product-addon relations
            $table->unique(['product_id', 'addon_id', 'deleted_at'], 'unq_product_addon');

            // Indexes for performance
            $table->index(['product_id'], 'idx_product_addons_product');
            $table->index(['addon_id'], 'idx_product_addons_addon');
            $table->index(['product_id', 'display_order'], 'idx_product_addons_order');
            $table->index(['deleted_at'], 'idx_addons_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_addons');
    }
};
