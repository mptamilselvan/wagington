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
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('order_number')->nullable();
            //$table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_booking', function (Blueprint $table) {
            $table->dropColumn('order_id');
            $table->dropColumn('order_number');
        });
    }
};
