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
            $table->timestamp('secondary_email_verified_at')->nullable()->after('secondary_country_code');
            $table->timestamp('secondary_phone_verified_at')->nullable()->after('secondary_email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['secondary_email_verified_at', 'secondary_phone_verified_at']);
        });
    }
};
