<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: guest_cart_addons
     * Purpose: Stores add-on items associated with a specific guest cart item.
     */
    public function up(): void
    {
        Schema::create('guest_cart_addons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('guest_cart_item_id');
            $table->unsignedBigInteger('addon_product_id')->nullable();
            $table->unsignedBigInteger('addon_variant_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->boolean('is_required')->default(false); // NEW: Field to denote a mandatory add-on.

            $table->timestamps();

            // CASCADE DELETE for guest cart addons
            $table->foreign('guest_cart_item_id')->references('id')->on('guest_cart_items')->onDelete('cascade');
            // CHANGE: Null on delete to prevent the cart from disappearing if a product/variant is deleted
            $table->foreign('addon_product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('addon_variant_id')->references('id')->on('product_variants')->onDelete('set null');

            // Enhanced indexing for quick lookups
            $table->index(['guest_cart_item_id'], 'idx_guest_cart_addons_item');
            $table->index(['addon_product_id'], 'idx_guest_cart_addons_product');
            $table->index(['addon_variant_id'], 'idx_guest_cart_addons_variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_cart_addons');
    }
};
