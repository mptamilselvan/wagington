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
        Schema::table('services', function (Blueprint $table) {
            $table->renameColumn('is_addon', 'has_addon');
            // $table->boolean('has_addon')->default(false)->change();
            $table->boolean('service_addon')->default(false);
            $table->decimal('total_price', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // $table->boolean('is_addon')->default(false)->change();
            $table->renameColumn('has_addon', 'is_addon');
            $table->dropColumn('service_addon');
            $table->dropColumn('total_price');
        });
    }
};
