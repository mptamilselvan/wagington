<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: order_vouchers
     * Purpose: Stores applied vouchers for each order (multi-coupon support)
     */
    public function up(): void
    {
        Schema::create('order_vouchers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('voucher_id');
            $table->string('voucher_code', 32);
            $table->enum('discount_type', ['percentage', 'amount']);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('calculated_discount', 10, 2); // Actual $ amount saved
            $table->decimal('running_total_after', 10, 2); // Amount left after this voucher applied
            $table->unsignedTinyInteger('stack_order'); // 1, 2, 3... order applied
            $table->unsignedSmallInteger('stack_priority'); // Calculated priority for sorting
            $table->timestamps();

            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('cascade');

            // Indexes
            $table->index(['order_id'], 'idx_order_vouchers_order');
            $table->index(['voucher_id'], 'idx_order_vouchers_voucher');
            $table->index(['order_id', 'stack_order'], 'idx_order_vouchers_order_stack');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_vouchers');
    }
};