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
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('pool_id')->nullable()->change();
            $table->unsignedBigInteger('pool_id')->nullable()->change();
            $table->unsignedBigInteger('species_id')->nullable()->change();

            // Core fields
            $table->string('limo_type')->nullable()->change();

            DB::statement("ALTER TABLE services ADD CONSTRAINT check_limo_type CHECK (limo_type IN ('pickup','drop_off','pickup_and_dropoff'))");


            // $table->enum('limo_type', ['pickup','drop_off', 'pickup_and_dropoff'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('pool_id')->change();
            $table->unsignedBigInteger('pool_id')->change();
            $table->unsignedBigInteger('species_id')->change();

            // Core fields
            $table->enum('limo_type', ['pickup','drop_off', 'pickup_and_dropoff'])->change();
        });
    }
};
