<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blood_test_records', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    
        Schema::table('blood_test_records', function (Blueprint $table) {
            $table->enum('status', ['positive', 'negative'])->default('positive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('blood_test_records', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    
        Schema::table('blood_test_records', function (Blueprint $table) {
            $table->enum('status', ['active', 'expired'])->default('active');
        });
    }
};
