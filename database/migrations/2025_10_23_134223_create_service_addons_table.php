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
        Schema::create('services_addons', function (Blueprint $table) {
            $table->id();

            // Foreign keys with explicit names
            $table->unsignedBigInteger('service_id')->index();
            $table->unsignedBigInteger('service_addon_id')->index();

            $table->boolean('status')->default(true)->index();
            $table->integer('display_order')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            // Add foreign key constraints with explicit names to avoid duplicates
            $table->foreign('service_id', 'services_addons_service_id_fk')
                  ->references('id')->on('services')->onDelete('cascade');

            $table->foreign('service_addon_id', 'services_addons_addon_id_fk')
                  ->references('id')->on('services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services_addons');
    }
};
