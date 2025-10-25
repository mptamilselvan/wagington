<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add fields for Stripe invoice information
            $table->string('invoice_id')->nullable()->after('transaction_id');
            // Use text() to avoid truncation of long external invoice URLs (Stripe, S3 presigned URLs, etc.)
            $table->text('invoice_url')->nullable()->after('invoice_id');
            $table->text('invoice_pdf_url')->nullable()->after('invoice_url');
            $table->string('invoice_number')->nullable()->after('invoice_pdf_url');
            
            // Add indexes for efficient lookups
            $table->index(['invoice_id'], 'idx_payments_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_invoice_id');
            $table->dropColumn(['invoice_id', 'invoice_url', 'invoice_pdf_url', 'invoice_number']);
        });
    }
};