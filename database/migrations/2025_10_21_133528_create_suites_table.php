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
        Schema::create('rooms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 32);
            //$table->unsignedBigInteger('species_id');
            //$table->unsignedBigInteger('service_category_id');
            $table->unsignedBigInteger('room_type_id');
            //$table->foreign('species_id')->references('id')->on('species')->onDelete('cascade');
           // $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('cascade');
           // $table->foreign('room_type_id')->references('id')->on('room_types')->onDelete('cascade');
            //$table->string('room_size', 50)->nullable();
            //$table->integer('maximum_occupancy')->nullable();
            $table->longText('cctv_stream')->nullable();
            //$table->string('pet_size_allowed', 50)->nullable();
            //$table->json('price_options')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            //$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            //$table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
