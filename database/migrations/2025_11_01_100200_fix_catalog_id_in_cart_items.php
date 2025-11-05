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
        // Drop foreign key first
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['catalog_id']);
        });
        
        // Drop index using raw SQL with IF EXISTS
        DB::statement('DROP INDEX IF EXISTS cart_items_catalog_id_index');
        
        // Re-create foreign key
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreign('catalog_id')
                  ->references('id')
                  ->on('catalogs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['catalog_id']);
            $table->unsignedBigInteger('catalog_id')->nullable(false)->default(1)->change();
            $table->foreign('catalog_id')->references('id')->on('catalogs');
        });
    }
};