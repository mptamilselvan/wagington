<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// use DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->boolean('avs_license_expiry_bool')->default(false);
            $table->dropColumn('avs_license_expiry');
            $table->renameColumn('avs_license_expiry_bool', 'avs_license_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn('avs_license_expiry_bool');
            $table->date('avs_license_expiry',50)->nullable();
        });
    }
};
