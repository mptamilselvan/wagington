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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name',150);
            $table->string('uen_no',50);
            $table->unsignedBigInteger('country_id');
            $table->string('postal_code',50);
            $table->string('address_line1',255);
            $table->string('address_line2',255)->nullable();
            $table->string('contact_number',20);
            $table->string('support_email');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            // $table->foreign('country_id')->references('id')->on('countries');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
