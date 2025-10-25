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
        Schema::create('room_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 32);
            $table->unsignedBigInteger('species_id');
            $table->json('images')->nullable();
            $table->longText('room_attributes')->nullable();
            $table->longText('room_amenities')->nullable();
            $table->longText('room_description')->nullable();
            $table->longText('room_overview')->nullable();
            $table->longText('room_highlights')->nullable();
            $table->longText('room_terms_and_conditions')->nullable();
            $table->json('service_addons')->nullable();
            $table->json('aggreed_terms')->nullable();
            $table->boolean('evaluation_required')->default(false);
            $table->integer('default_clean_minutes')->nullable();
            $table->integer('turnover_buffer_min')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('room_types');
    }
};
