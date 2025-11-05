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
        Schema::table('room_booking', function (Blueprint $table) {
            $table->string('payment_status')->default('')->nullable()->change();
            $table->string('booking_status')->default('')->nullable()->change();

        });

            DB::statement("ALTER TABLE room_booking DROP CONSTRAINT IF EXISTS room_booking_payment_status_check");
            DB::statement("ALTER TABLE room_booking DROP CONSTRAINT IF EXISTS room_booking_booking_status_check");

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_booking', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending')->change();
            $table->enum('booking_status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending')->change();
        });
    }
};
