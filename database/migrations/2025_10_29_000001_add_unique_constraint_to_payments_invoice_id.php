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
            // First drop the existing non-unique index if it exists
            if (Schema::hasColumn('payments', 'invoice_id')) {
                try {
                    $table->dropIndex('idx_payments_invoice_id');
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
            }
            
            // Add unique constraint to ensure invoice_id uniqueness
            $table->unique(['invoice_id'], 'idx_payments_invoice_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('idx_payments_invoice_id_unique');
            
            // Restore the original non-unique index
            $table->index(['invoice_id'], 'idx_payments_invoice_id');
        });
    }
};