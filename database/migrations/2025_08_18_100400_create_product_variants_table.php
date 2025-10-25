<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Table: product_variants
     * Purpose: Stores different versions of a product (like different sizes or colors) with their
     * specific pricing and inventory. Each variant represents a unique SKU.
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Links to the parent product
            $table->unsignedBigInteger('product_id');

            // Selected option values for this variant (e.g., {"Color":"Red","Size":"L"})
            // Use a meaningful name to avoid ambiguity
            $table->json('variant_attributes')->nullable(); // renamed usage in code from 'attributes' to 'variant_attributes'

            // NOTE: Removed 'variant_name' column in favor of structured 'variant_attributes'
            // If needed for display, compute from attributes at runtime.

            // Unique stock keeping unit (e.g., "DOG-TOY-RED-L")
            $table->string('sku', 100);

            // Barcode for the variant
            $table->string('barcode', 100)->nullable();

            // The default variant to show first
            $table->boolean('is_primary')->default(false);

            // Wholesale cost (for profit calculation)
            $table->decimal('cost_price', 10, 2)->nullable();

            // Current selling price
            $table->decimal('selling_price', 10, 2);

            // Original/strikethrough price for sales
            $table->decimal('compare_price', 10, 2)->nullable();

            // Current number of items in stock
            $table->integer('stock_quantity')->default(0);

            // It tracks how many of those items are currently sitting in users' carts
            $table->integer('reserved_stock')->default(0);

            $table->integer('sold_stock')->default(0);

            // When stock reaches this number, trigger alert
            $table->integer('min_quantity_alert')->default(5);

            // Limits how many can be ordered at once
            $table->integer('max_quantity_per_order')->default(10);

            // When FALSE, this variant is treated as always in stock
            $table->boolean('track_inventory')->default(true);

            // When TRUE, customers can order when out of stock
            $table->boolean('allow_backorders')->default(false);

            // Used for shipping calculations
            $table->decimal('weight_kg', 8, 3)->nullable();

            // Package dimensions for shipping
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();

            // Controls variant visibility
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            // FK and cascade behavior
            $table->foreign('product_id')->references('id')->on('products');

            // Indexes
            $table->index(['product_id'], 'idx_variants_product');
            $table->index(['stock_quantity', 'min_quantity_alert'], 'idx_variants_stock_alert');
            $table->index(['selling_price'], 'idx_variants_price');
            $table->index(['deleted_at'], 'idx_variants_deleted');
        });

        // Partial unique index on sku where deleted_at IS NULL (PostgreSQL)
        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS unq_variants_sku ON product_variants (sku) WHERE deleted_at IS NULL;");

        // Ensure reserved_stock is non-negative (portable check via trigger alternative can be added per-driver)
        try {
            $driver = DB::getDriverName();
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE product_variants ADD CONSTRAINT chk_reserved_stock_nonneg CHECK (reserved_stock >= 0);");
            }
        } catch (\Throwable $e) { /* skip if not supported */ }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unq_variants_sku');
        Schema::dropIfExists('product_variants');
    }
};