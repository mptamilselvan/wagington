<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations: Renames the old coupon column and adds the new strikethrough savings column.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            
            // 1. RENAME: Rename the existing 'discount_amount' column. It shows the total coupon discount amount applied to the order.
            $table->renameColumn('discount_amount', 'coupon_discount_amount');
            
            // 2. ADD: Add the new column for savings derived from (Strikethrough Price - Selling Price)
            $table->decimal('strikethrough_discount_amount', 10, 2)
                  ->default(0)
                  ->after('coupon_discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 1. REVERSE ADD: Drop the newly added column
            $table->dropColumn('strikethrough_discount_amount');
            
            // 2. REVERSE RENAME: Restore the old column name
            $table->renameColumn('coupon_discount_amount', 'discount_amount');
        });
    }
};