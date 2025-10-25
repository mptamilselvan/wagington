<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: orders
     * Purpose: Stores the core information for each customer order.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');    

            // Unique order identifier (e.g., 'ORD-20250901-0001')
            $table->string('order_number', 100)->unique();
            $table->unsignedBigInteger('user_id');

            // Address links (nullable to preserve order history if addresses are removed)
            $table->unsignedBigInteger('shipping_address_id')->nullable();
            $table->unsignedBigInteger('billing_address_id')->nullable();

            // Order lifecycle status (fulfillment)
            // CHANGE: Added 'payment_failed' status to allow for payment retries.
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'partially_shipped',
                'partially_backordered',
                'backordered',
                'shipped',
                'delivered',
                'cancelled',
                'refunded',
                'payment_failed'
            ]);
            
            // NEW: Tracks the number of failed payment attempts for this order.
            $table->unsignedTinyInteger('payment_failed_attempts')->default(0);

            // Shipping metadata
            $table->string('shipping_method', 100)->nullable();
            $table->string('tracking_number', 150)->nullable();
            $table->date('estimated_delivery')->nullable();

            // Financial breakdown (CRITICAL for reports & refunds)
            $table->decimal('subtotal', 10, 2);           // Sum of items before discounts
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);       // Final amount

            // Extras for display and reconciliation
            $table->string('coupon_code', 100)->nullable();

            // CHANGE: Removed payment_status and card_last4 as they are now in the payments table.

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('shipping_address_id')->references('id')->on('addresses')->onDelete('set null');
            $table->foreign('billing_address_id')->references('id')->on('addresses')->onDelete('set null');

            // Enhanced indexing for dashboard queries
            $table->index(['user_id', 'status'], 'idx_orders_user_status');
            $table->index(['status', 'created_at'], 'idx_orders_status_date');
            $table->index(['shipping_address_id'], 'idx_orders_shipping_addr');
            $table->index(['billing_address_id'], 'idx_orders_billing_addr');

            // Crucial index for soft deletes
            $table->index(['deleted_at'], 'idx_orders_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
