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

        Schema::create('referral_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('base_promotion_id')
                ->constrained('base_promotions')
                ->onDelete('cascade');
            $table->enum('discount_type', ['percentage', 'amount']);
            $table->decimal('referrer_reward', 10, 2)->default(0);
            $table->decimal('referee_reward', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_promotions');
    }
};
