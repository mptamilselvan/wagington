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
        Schema::table('service_types', function (Blueprint $table) {
            $table->string('slug', 200)->unique()->nullable();
            $table->string('description',200)->nullable();
            $table->string('image')->nullable();
            $table->string('meta_title',50)->nullable();
            $table->string('meta_description',200)->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('focus_keywords')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('description');
            $table->dropColumn('image');
            $table->dropColumn('meta_title');
            $table->dropColumn('meta_description');
            $table->dropColumn('meta_keywords');
            $table->dropColumn('focus_keywords');

        });
    }
};
