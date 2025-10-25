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
        Schema::create('medication_supplement_admin_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('medication_supplement_id');
            $table->string('administer_name',50);
            $table->date('date');
            $table->time('time');
            $table->string('administer_notes',200)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('medication_supplement_id')->references('id')->on('medication_supplements')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_supplement_admin_details');
    }
};
