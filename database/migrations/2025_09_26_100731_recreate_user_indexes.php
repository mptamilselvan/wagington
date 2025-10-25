<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        // Drop existing indexes first
        self::dropIndexIfExists('users', 'users_first_name_index');
        self::dropIndexIfExists('users', 'users_last_name_index');
        self::dropIndexIfExists('users', 'users_phone_index');
        self::dropIndexIfExists('users', 'users_is_active_index');
        self::dropIndexIfExists('users', 'users_phone_verified_at_index');
        self::dropIndexIfExists('users', 'users_active_email_verified_index');
        self::dropIndexIfExists('users', 'users_active_phone_verified_index');
        self::dropIndexIfExists('users', 'users_active_verified_index');
        self::dropIndexIfExists('users', 'users_created_at_index');

        // Recreate indexes only if columns exist
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'first_name')) {
                $table->index('first_name', 'users_first_name_index');
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $table->index('last_name', 'users_last_name_index');
            }
            if (Schema::hasColumn('users', 'phone')) {
                $table->index('phone', 'users_phone_index');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->index('is_active', 'users_is_active_index');
            }
            if (Schema::hasColumn('users', 'phone_verified_at')) {
                $table->index('phone_verified_at', 'users_phone_verified_at_index');
            }
            if (Schema::hasColumn('users', 'email_verified_at') && Schema::hasColumn('users', 'is_active')) {
                $table->index(['is_active', 'email_verified_at'], 'users_active_email_verified_index');
            }
            if (Schema::hasColumn('users', 'phone_verified_at') && Schema::hasColumn('users', 'is_active')) {
                $table->index(['is_active', 'phone_verified_at'], 'users_active_phone_verified_index');
            }
            if (
                Schema::hasColumn('users', 'is_active') &&
                Schema::hasColumn('users', 'email_verified_at') &&
                Schema::hasColumn('users', 'phone_verified_at')
            ) {
                $table->index(['is_active', 'email_verified_at', 'phone_verified_at'], 'users_active_verified_index');
            }
            if (Schema::hasColumn('users', 'created_at')) {
                $table->index('created_at', 'users_created_at_index');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        self::dropIndexIfExists('users', 'users_first_name_index');
        self::dropIndexIfExists('users', 'users_last_name_index');
        self::dropIndexIfExists('users', 'users_phone_index');
        self::dropIndexIfExists('users', 'users_is_active_index');
        self::dropIndexIfExists('users', 'users_phone_verified_at_index');
        self::dropIndexIfExists('users', 'users_active_email_verified_index');
        self::dropIndexIfExists('users', 'users_active_phone_verified_index');
        self::dropIndexIfExists('users', 'users_active_verified_index');
        self::dropIndexIfExists('users', 'users_created_at_index');
    }

    private static function dropIndexIfExists(string $table, string $index): void
    {
        $exists = DB::table('pg_indexes')
            ->where('tablename', $table)
            ->where('indexname', $index)
            ->exists();

        if ($exists) {
            Schema::table($table, function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        }
    }
};
