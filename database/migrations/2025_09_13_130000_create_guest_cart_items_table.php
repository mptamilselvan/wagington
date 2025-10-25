<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: guest_cart_items
     * Purpose: Store cart items for non-authenticated sessions.
     */
    public function up(): void
    {
        Schema::create('guest_cart_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('session_token', 120);
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->integer('quantity');

            // Replaced 'is_backorder' with a more descriptive enum
            $table->enum('availability_status', ['in_stock', 'backordered', 'partially_backordered'])->default('in_stock');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Changed from 'cascade' to 'set null' for data integrity
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('variant_id')->references('id')->on('product_variants')->onDelete('set null');

            $table->unique(['session_token', 'product_id', 'variant_id'], 'uq_guest_cart_items_session_product_variant');
            $table->index(['session_token'], 'idx_guest_cart_items_session');
            $table->index(['expires_at'], 'idx_guest_cart_items_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_cart_items');
    }
};