<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            // Country/Region code, e.g., 'SG', 'US', or a named region key
            $table->string('region', 50)->index();

            // Ranges
            $table->decimal('weight_min', 8, 2)->unsigned()->nullable();
            $table->decimal('weight_max', 8, 2)->unsigned()->nullable();
            $table->decimal('volume_min', 12, 2)->unsigned()->nullable();
            $table->decimal('volume_max', 12, 2)->unsigned()->nullable();

            $table->decimal('cost', 10, 2)->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};