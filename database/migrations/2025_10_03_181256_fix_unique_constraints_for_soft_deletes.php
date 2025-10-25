<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing unique constraint that doesn't account for soft deletes
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
        
        // Create partial unique indexes that exclude soft-deleted records
        // For email: unique only where deleted_at IS NULL
        DB::statement('CREATE UNIQUE INDEX users_email_unique_not_deleted ON users (email) WHERE deleted_at IS NULL');
        
        // For phone combinations: unique only where deleted_at IS NULL
        DB::statement('CREATE UNIQUE INDEX users_phone_unique_not_deleted ON users (country_code, phone) WHERE deleted_at IS NULL');
        
        // For secondary email: prevent user from using their own primary email as secondary
        if (Schema::hasColumn('users', 'secondary_email')) {
            DB::statement('ALTER TABLE users ADD CONSTRAINT users_secondary_email_not_same_as_primary CHECK (secondary_email IS NULL OR secondary_email != email)');
        }
        
        // For secondary phone: prevent user from using their own primary phone as secondary
        if (Schema::hasColumn('users', 'secondary_phone') && Schema::hasColumn('users', 'secondary_country_code')) {
            DB::statement('ALTER TABLE users ADD CONSTRAINT users_secondary_phone_not_same_as_primary CHECK (secondary_phone IS NULL OR secondary_country_code IS NULL OR secondary_phone != phone OR secondary_country_code != country_code)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the partial unique indexes and check constraints
        DB::statement('DROP INDEX IF EXISTS users_email_unique_not_deleted');
        DB::statement('DROP INDEX IF EXISTS users_phone_unique_not_deleted');
        
        // Safely drop check constraints (using try/catch for better cross-database compatibility
        // as IF EXISTS for ALTER TABLE is not universal).
        try {
            DB::statement('ALTER TABLE users DROP CONSTRAINT users_secondary_email_not_same_as_primary');
        } catch (\Exception $e) { /* Ignore if constraint doesn't exist */ }
        
        try {
            DB::statement('ALTER TABLE users DROP CONSTRAINT users_secondary_phone_not_same_as_primary');
        } catch (\Exception $e) { /* Ignore if constraint doesn't exist */ }

        
        // Recreate the original simple unique constraints (addressing the incomplete rollback)
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
            $table->unique(['country_code', 'phone']);
        });
    }
};
