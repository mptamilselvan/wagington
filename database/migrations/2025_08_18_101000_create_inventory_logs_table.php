<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Table: inventory_logs
     * Purpose: Records every change to inventory levels, creating an audit trail that shows
     * when and why stock levels changed (purchases, returns, adjustments, etc.)
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // The product that was changed
            $table->unsignedBigInteger('product_id');

            // The specific variant that was changed
            $table->unsignedBigInteger('variant_id');

            // Positive for stock in, negative for stock out
            $table->integer('quantity');

            // A sale entry is created immediately after a successful payment.
            // A shipment entry is created when a shipping label is generated for an order, often as a separate step after payment.
            $table->enum('action', ['stock_in', 'stock_out', 'adjustment', 'sale', 'return', 'damaged', 'sale_backorder', 'shipment']);

            // Explanation for the change
            $table->text('reason')->nullable();

            // Related order/return ID (if applicable)
            $table->unsignedBigInteger('reference_id')->nullable();

            // What type of document caused this change
            $table->string('reference_type', 50)->nullable();

            // Which user made the change (NULL for system)
            $table->unsignedBigInteger('user_id')->nullable();

            // The new stock level after this change
            $table->integer('stock_after');

            $table->timestamp('created_at')->useCurrent();

            // FKs
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('variant_id')->references('id')->on('product_variants');

            // Composite index for lookups by reference
            $table->index(['reference_type', 'reference_id'], 'idx_inventory_reference');
        });

        // Ordered index with DESC for created_at (PostgreSQL supports sort order in indexes)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_inventory_variant_date ON inventory_logs (variant_id, created_at DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_inventory_variant_date');
        Schema::dropIfExists('inventory_logs');
    }
};