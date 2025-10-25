<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table: payments
     * Purpose: Stores a record of every payment attempt for an order.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            // Primary key for the payments table.
            $table->bigIncrements('id');

            // Foreign key to link this payment to a specific order.
            $table->unsignedBigInteger('order_id');

            // The unique transaction ID from the payment gateway.
            $table->string('transaction_id')->unique();

            // The payment gateway used (e.g., 'stripe', 'paypal').
            $table->string('payment_gateway');

            // The status of this specific payment attempt.
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded', 'voided']);

            // The amount for this payment attempt.
            $table->decimal('amount', 10, 2);

            // The last 4 digits of the card for customer reference.
            $table->string('card_last4', 4)->nullable();

            $table->timestamps();

            // Foreign key constraints.
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');

            // Indexes for efficient lookups.
            $table->index(['order_id'], 'idx_payments_order');
            $table->index(['status', 'created_at'], 'idx_payments_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

