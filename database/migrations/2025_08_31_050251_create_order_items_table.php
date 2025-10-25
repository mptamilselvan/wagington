<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: order_items
     * Purpose: Stores the individual items within an order.
     * Includes "snapshot" fields to preserve order history even if products or variants
     * are deleted from the main product catalog.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');

            // Foreign key references (can be null if product/variant deleted)
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();

            // SNAPSHOT FIELDS (preserve order history even if products deleted)
            $table->string('product_name', 255);          // Product name at time of order
            $table->string('variant_display_name', 255)->nullable(); // Variant display name (computed) at time of order
            $table->string('sku', 100)->nullable();       // SKU at time of order
            $table->json('product_attributes')->nullable(); // Variant attributes snapshot

            // Pricing and quantity
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('reserved_quantity')->default(0); // number of units that were in-stock at purchase time
            $table->unsignedInteger('fulfilled_quantity')->default(0); // number of units shipped so far
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);

            $table->timestamps();

            // NULL ON DELETE strategy (preserves order history)
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('variant_id')->references('id')->on('product_variants')->nullOnDelete();

            // Enhanced indexing
            $table->index(['order_id', 'product_id'], 'idx_order_items_order_product');
            $table->index(['product_id'], 'idx_order_items_product');
            $table->index(['variant_id'], 'idx_order_items_variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};