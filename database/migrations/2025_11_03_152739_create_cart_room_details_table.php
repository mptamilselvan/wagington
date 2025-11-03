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
        Schema::create('cart_room_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_item_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('room_type_id');
            $table->unsignedBigInteger('customer_id');
            $table->json('pets_reserved')->nullable()->comment('JSON array of pet IDs');
            $table->json('service_addons')->nullable()->comment('JSON array of service addon IDs');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('no_of_days')->default(0);
            $table->decimal('room_price', 10, 2)->default(0);
            $table->decimal('addons_price', 10, 2)->default(0);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->integer('pet_quantity')->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_room_details');
    }
};
