<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a unique index to the payments.invoice_number column to prevent duplicates.
     * Note: This migration assumes the `invoice_number` column already exists (nullable string).
     * If there are existing duplicate values, this migration will fail - please clean duplicates first.
     *
     * @return void
     */
    public function up(): void
    {
        // Ensure the column exists before trying to add index
        if (Schema::hasColumn('payments', 'invoice_number')) {
            Schema::table('payments', function (Blueprint $table) {
                // Use a named index so it can be referenced/rolled back reliably
                $table->unique('invoice_number', 'payments_invoice_number_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (Schema::hasColumn('payments', 'invoice_number')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique('payments_invoice_number_unique');
            });
        }
    }
};
