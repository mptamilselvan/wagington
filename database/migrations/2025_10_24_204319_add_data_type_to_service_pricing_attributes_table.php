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
        Schema::table('service_pricing_attributes', function (Blueprint $table) {
            $table->string('data_type')->after('value')->nullable()->comment('Data type of the attribute value, e.g., text, integer, time,decimal etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_pricing_attributes', function (Blueprint $table) {
            $table->dropColumn('data_type');
        });
    }
};
