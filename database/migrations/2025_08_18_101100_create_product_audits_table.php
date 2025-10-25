<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Table: product_audits
     * Purpose: Tracks all changes made to products for compliance, debugging, and accountability.
     * Creates a complete history of who changed what and when.
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('product_audits', function (Blueprint $table) {
            $table->bigIncrements('id');

            // The product that was changed
            $table->unsignedBigInteger('product_id');

            // Who made the change
            $table->unsignedBigInteger('user_id');

            $table->enum('action', ['create', 'update', 'delete', 'restore'])->default('update');

            // Which field was changed
            $table->string('field_name', 100)->nullable();

            // Previous value
            $table->text('old_value')->nullable();

            // New value
            $table->text('new_value')->nullable();

            // IP address of the user
            $table->string('ip_address', 45)->nullable();

            // Browser/device info
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // FK
            $table->foreign('product_id')->references('id')->on('products');

            // Indexes
            $table->index(['user_id'], 'idx_audits_user');
        });

        // Ordered index with DESC on created_at for product-based audit lookups
        DB::statement('CREATE INDEX IF NOT EXISTS idx_audits_product_date ON product_audits (product_id, created_at DESC)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_audits_product_date');
        Schema::dropIfExists('product_audits');
    }
};