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
        Schema::create('services_booking_slots', function (Blueprint $table) {
            $table->id();

            // Foreign key
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete()->index();

            // Booking slot fields
            $table->string('day', 50);
            $table->time('start_time');
            $table->time('end_time');

            $table->timestamps();

            // Optional composite index for performance
            $table->index(['service_id', 'day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services_booking_slots');
    }
};
