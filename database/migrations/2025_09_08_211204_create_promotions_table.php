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
        Schema::create('base_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name',50);
            $table->string('description',200)->nullable();
            $table->string('promo_code', 32)->unique()->nullable();
            $table->string('terms_and_conditions',200)->nullable();
            $table->dateTime('valid_from');
            $table->dateTime('valid_till');
            $table->unsignedSmallInteger('coupon_validity')->default(60);
            $table->string('promotion', 64)->nullable();
            $table->boolean('published')->default(true);
            $table->boolean('stackable')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('base_promotion_id')->constrained('base_promotions')->onDelete('cascade');
            $table->enum('discount_type', ['percentage', 'amount']);
            $table->decimal('discount_value', 10, 2);
            $table->enum('usage_type', ['single_use', 'multiple_use', 'unlimited']);
            $table->unsignedInteger('customer_usage_limit')->default(1);
            $table->enum('customer_type', ['all', 'new', 'selected'])->default('all');
            $table->json('selected_customer_ids')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
        Schema::dropIfExists('base_promotions');
    }
};
