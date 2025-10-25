<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: cart_items
     * Purpose: Stores the individual items within a user's shopping cart.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            // Replaced 'is_backorder' with a more descriptive enum
            $table->enum('availability_status', ['in_stock', 'backordered', 'partially_backordered'])->default('in_stock');
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // Changed from 'cascade' to 'set null' for data integrity
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('set null');

            // Add generated column for variant uniqueness that converts NULL to sentinel value
            $table->bigInteger('variant_key')->storedAs('COALESCE(variant_id, -1)');
            
            // Use the generated column in unique constraint instead of nullable variant_id
            $table->unique(['user_id', 'variant_key']);
            $table->index(['user_id'], 'idx_cart_items_user');
            $table->index(['expires_at'], 'idx_cart_items_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};