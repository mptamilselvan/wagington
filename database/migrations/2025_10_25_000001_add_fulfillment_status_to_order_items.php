<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add fulfillment_status column to order_items table
     * This tracks the fulfillment status of individual order items
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('fulfillment_status', 50)->default('pending')->after('fulfilled_quantity');
            
            // Add index for performance
            $table->index(['fulfillment_status'], 'idx_order_items_fulfillment_status');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_fulfillment_status');
            $table->dropColumn('fulfillment_status');
        });
    }
};
