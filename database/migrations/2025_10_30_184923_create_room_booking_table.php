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
        Schema::create('room_booking', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('room_type_id');
            $table->unsignedBigInteger('customer_id');
            $table->decimal('room_price', 10, 2)->default(0);
            $table->string('room_price_label')->nullable();
            $table->json('pets_reserved')->nullable()->comment('JSON array of pet IDs');
            $table->unsignedBigInteger('species_id');
            $table->integer('pet_quantity');
            $table->json('service_addons')->nullable();
            $table->boolean('is_peak_season')->default(false);
            $table->boolean('is_off_day')->default(false);
            $table->boolean('is_weekend')->default(false);
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('no_of_days')->default(0);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->enum('booking_status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_booking');
    }
};
