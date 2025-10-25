<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: cart_addons
     * Purpose: Stores add-on items associated with a specific cart item.
     */
    public function up(): void
    {
        Schema::create('cart_addons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('cart_item_id');
            $table->unsignedBigInteger('addon_product_id')->nullable();
            $table->unsignedBigInteger('addon_variant_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->boolean('is_required')->default(false); // NEW: Field to denote a mandatory add-on.

            $table->timestamps();

            // CASCADE DELETE for cart addons (safe to remove when cart item is deleted)
            $table->foreign('cart_item_id')->references('id')->on('cart_items')->onDelete('cascade');
            // CHANGE: Null on delete to prevent the cart from disappearing if a product/variant is deleted
            $table->foreign('addon_product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('addon_variant_id')->references('id')->on('product_variants')->onDelete('set null');

            $table->index(['cart_item_id'], 'idx_cart_addons_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_addons');
    }
};
