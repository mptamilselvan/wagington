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
        Schema::table('cart_items', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['catalog_id']);
            
            // Make the column nullable first (if choosing SET NULL behavior)
            $table->unsignedBigInteger('catalog_id')->nullable()->change();
            
            // Add the new foreign key with explicit referential actions
            $table->foreign('catalog_id')
                  ->references('id')
                  ->on('catalogs')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['catalog_id']);
            
            // Restore the column to non-nullable
            $table->unsignedBigInteger('catalog_id')->nullable(false)->change();
            
            // Restore the original foreign key without explicit actions
            $table->foreign('catalog_id')
                  ->references('id')
                  ->on('catalogs');
        });
    }
};