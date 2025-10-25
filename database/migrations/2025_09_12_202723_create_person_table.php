<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $referral = strtoupper(Str::random(6));

            // make sure referral_code is unique
            while (DB::table('users')->where('referal_code', $referral)->exists()) {
                $referral = strtoupper(Str::random(6));
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'referal_code' => $referral,
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('referal_code', 32)->unique()->nullable()->change()->after('id');
            $table->foreignId('referred_by_id')->nullable()->constrained('users')->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('referral_code', 'referal_code');
            $table->dropConstrainedForeignId('referred_by_id');
        });
    }
};
