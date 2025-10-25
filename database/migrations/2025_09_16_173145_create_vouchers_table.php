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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')
            ->constrained('base_promotions')
            ->cascadeOnDelete();
            $table->string('voucher_code', 32)->nullable();
            $table->foreignId('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->enum('discount_type', ['percentage', 'amount']);
            $table->enum('voucher_type', [
                'referrer_reward',
                'referee_reward',
                'sales_person',
                'marketing_campaign'
            ])->nullable();

            $table->decimal('discount_value', 10, 2);
            $table->unsignedInteger('max_usage')->default(1);
            $table->unsignedInteger('usage_count')->default(0);

            $table->enum('status', ['pending', 'available', 'redeemed', 'expired']);

            $table->timestamp('valid_till')->nullable();

            $table->foreignId('referee_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['customer_id', 'voucher_code'], 'voucher_once_per_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
