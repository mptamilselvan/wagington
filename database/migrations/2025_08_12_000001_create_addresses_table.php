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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade'); // Applies on hard delete; soft deletes handled in model events

            $table->foreignId('address_type_id')
                ->nullable()
                ->constrained('address_types')
                ->nullOnDelete();

            // Core fields
            $table->string('label')->nullable();
            $table->string('country');
            $table->string('postal_code', 20);
            $table->string('address_line1');
            $table->string('address_line2')->nullable();

            // Flags
            $table->boolean('is_billing_address')->default(false);
            $table->boolean('is_shipping_address')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Optional helpful indexes
            $table->index(['user_id', 'is_billing_address']);
            $table->index(['user_id', 'is_shipping_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};