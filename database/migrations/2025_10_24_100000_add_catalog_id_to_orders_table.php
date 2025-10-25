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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('catalog_id')->default(1)->after('user_id');
            $table->foreign('catalog_id')->references('id')->on('catalogs');
            $table->index('catalog_id', 'idx_orders_catalog');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['catalog_id']);
            $table->dropIndex('idx_orders_catalog');
            $table->dropColumn('catalog_id');
        });
    }
};