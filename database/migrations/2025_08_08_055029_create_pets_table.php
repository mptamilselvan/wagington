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
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name',50);
            $table->string('profile_image')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->unsignedBigInteger('species_id');
            $table->unsignedBigInteger('breed_id');
            $table->string('color',50);
            $table->date('date_of_birth');
            $table->integer('age_months');
            $table->boolean('sterilisation_status')->default(true); 
            $table->string('microchip_number',50)->nullable();
            $table->decimal('length_cm')->nullable();
            $table->decimal('height_cm')->nullable();
            $table->decimal('weight_kg')->nullable();
            $table->string('avs_license_number',50)->nullable();
            $table->date('avs_license_expiry',50)->nullable();
            $table->date('date_expiry',50)->nullable();
            $table->string('document')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('species_id')->references('id')->on('species')->onDelete('cascade');
            $table->foreign('breed_id')->references('id')->on('breeds')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
