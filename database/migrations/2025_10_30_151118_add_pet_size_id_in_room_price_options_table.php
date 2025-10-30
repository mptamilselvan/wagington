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
        Schema::table('room_price_options', function (Blueprint $table) {
            $table->unsignedBigInteger('pet_size_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_price_options', function (Blueprint $table) {
            $table->dropColumn('pet_size_id');
        });
    }
};
