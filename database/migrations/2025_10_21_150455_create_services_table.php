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
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            // Foreign keys (nullable for flexibility)
            $table->unsignedBigInteger('catalog_id');
            $table->unsignedBigInteger('service_type_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('subcategory_id');
            $table->unsignedBigInteger('pool_id');
            $table->unsignedBigInteger('species_id');

            // Core fields
            $table->enum('limo_type', ['pickup','drop_off', 'pickup_and_dropoff']);
            $table->string('title', 200);
            $table->string('slug', 200)->unique();
            $table->string('overview',200)->nullable();
            $table->string('description',200)->nullable();
            $table->string('highlight',200)->nullable();
            $table->string('terms_and_conditions',200)->nullable();

            // Media / SEO fields
            $table->json('images');
            $table->boolean('is_addon')->default(false);
            $table->string('meta_title',50)->nullable();
            $table->string('meta_description',200)->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('focus_keywords')->nullable();

            // Configuration
            $table->json('agreed_terms')->nullable();
            $table->boolean('pet_selection_required')->default(false);
            $table->boolean('evaluvation_required')->default(false);
            $table->boolean('is_shippable')->default(false);
            $table->boolean('limo_pickup_dropup_address')->default(false);
            
            // Pricing
            $table->enum('pricing_type', ['fixed','advance', 'distance_based'])->nullable()->index();
            $table->json('pricing_attributes')->nullable();
            $table->boolean('booking_slot_flag')->default(false);
            
            // Hierarchy and details
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('lable', 255)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('no_humans')->nullable();
            $table->integer('no_pets')->nullable();
            $table->decimal('duration', 8, 2)->nullable();
            $table->decimal('km_start', 8, 2)->nullable();
            $table->decimal('km_end', 8, 2)->nullable();
            $table->unsignedBigInteger('created_by')->index();
            $table->unsignedBigInteger('updated_by')->index();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('catalog_id')->references('id')->on('catalogs')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('service_categories')->onDelete('cascade');
            $table->foreign('subcategory_id')->references('id')->on('service_subcategories')->onDelete('cascade');
            // $table->foreign('service_type_id')->references('id')->on('service_types')->onDelete('cascade');
            $table->foreign('pool_id')->references('id')->on('pool_settings')->onDelete('cascade');
            $table->foreign('species_id')->references('id')->on('species')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index(['category_id'], 'idx_service_categories');
            $table->index(['subcategory_id'], 'idx_service_subcategories');
            $table->index('catalog_id', 'idx_service_catalog');
            $table->index('service_type_id', 'idx_service_types');
            $table->index('pool_id', 'idx_pool_settings');
            $table->index('species_id', 'idx_species');

            $table->index(['title'], 'idx_title');
            $table->index(['slug'], 'idx_slug');
            // This ensures uniqueness for active records only
            $table->unique(['slug', 'deleted_at'], 'unq_service_slug_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
