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
        Schema::table('vaccine_exemptions', function (Blueprint $table) {
            $table->dropColumn('blood_test_id'); // removes old integer
        });

        Schema::table('vaccine_exemptions', function (Blueprint $table) {
            $table->json('blood_test_id')->nullable(); // adds fresh JSON column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vaccine_exemptions', function (Blueprint $table) {
            $table->dropColumn('blood_test_id');
        });

        Schema::table('vaccine_exemptions', function (Blueprint $table) {
            $table->unsignedBigInteger('blood_test_id');
        });
    }
};
