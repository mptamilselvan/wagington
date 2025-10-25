<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guest_cart_items', function (Blueprint $table) {
            $table->unsignedBigInteger('catalog_id')->default(1)->after('session_token');
            $table->foreign('catalog_id')->references('id')->on('catalogs');
            $table->index('catalog_id', 'idx_guest_cart_items_catalog');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_cart_items', function (Blueprint $table) {
            $table->dropForeign(['catalog_id']);
            $table->dropIndex('idx_guest_cart_items_catalog');
            $table->dropColumn('catalog_id');
        });
    }
};