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
        Schema::table('users', function (Blueprint $table) {
            $table->string('salutation', 10)->nullable();
            
           // Phone fields (supporting both legacy and new format)
            $table->string('phone')->nullable();
            $table->string('country_code', 10)->default('+65');
            $table->timestamp('phone_verified_at')->nullable();
            
            // Customer profile fields
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('dob')->nullable();
            $table->string('passport_nric_fin_number')->nullable();
            $table->string('image')->nullable(); // Profile image path
            $table->string('referal_code')->nullable();
            
            // Secondary contact fields
            $table->string('secondary_first_name')->nullable();
            $table->string('secondary_last_name')->nullable();
            $table->string('secondary_email')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('secondary_country_code', 10)->nullable();
            
            // Status and audit fields
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('created_by')->nullable(); // Track which admin created user
            $table->unsignedBigInteger('updated_by')->nullable(); // Track which admin updated user            
           
            // Indexes for performance
            $table->index('is_active');
            $table->index(['country_code', 'phone']);

            $table->string('name')->nullable()->change();// Made nullable for customers who might not provide full name initially
            $table->string('email')->nullable()->change();// Made nullable to support phone-only registration
            $table->string('password')->nullable()->change();// Made nullable for OTP-only customers
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
