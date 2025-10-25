<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a nullable decimal column `applied_tax_rate` to the orders table to store the tax rate
     * that was applied at the time of order placement.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'applied_tax_rate')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('applied_tax_rate', 5, 2)->nullable()->after('tax_amount');
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
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'applied_tax_rate')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('applied_tax_rate');
            });
        }
    }
};
