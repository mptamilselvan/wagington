<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: order_addons
     * Purpose: Stores add-on items associated with a specific order item.
     * Also uses a "snapshot" approach to preserve historical data.
     */
    public function up(): void
    {
        Schema::create('order_addons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_item_id');

            // Foreign key references (can be null if addon deleted)
            $table->unsignedBigInteger('addon_product_id')->nullable();
            $table->unsignedBigInteger('addon_variant_id')->nullable();

            // SNAPSHOT FIELDS (preserve addon history)
            $table->string('addon_name', 255);
            $table->string('addon_variant_display_name', 255)->nullable();
            $table->string('addon_sku', 100)->nullable();
            $table->boolean('was_required');

            // Pricing and quantity
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('reserved_quantity')->default(0); // number of units that were in-stock at purchase time
            $table->unsignedInteger('fulfilled_quantity')->default(0); // number of units shipped so far
            $table->decimal('unit_price', 10, 2)->unsigned();
            $table->decimal('total_price', 10, 2)->unsigned();

            $table->timestamps();

            // NULL ON DELETE strategy (preserves order history)
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('addon_product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('addon_variant_id')->references('id')->on('product_variants')->nullOnDelete();

            $table->index(['order_item_id'], 'idx_order_addons_item');
            $table->index(['addon_product_id'], 'idx_order_addons_product');
            $table->index(['addon_variant_id'], 'idx_order_addons_variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_addons');
    }
};