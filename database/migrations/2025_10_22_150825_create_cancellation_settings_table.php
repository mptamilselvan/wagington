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
        Schema::create('cancellation_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('before_6_hour_percentage')->nullable();
            $table->integer('before_24_hour_percentage')->nullable();
            $table->integer('before_72_hour_percentage')->nullable();
            $table->integer('admin_cancel_percentage')->nullable();
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
        Schema::dropIfExists('cancellation_settings');
    }
};
