<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: variant_attribute_values
     * Purpose: Stores the actual attribute values for each attribute type (like "Small", "Medium", "Large" for Size).
     * These are the predefined options that can be selected when creating product variants.
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->bigIncrements('id');

            // The type of attribute this value belongs to
            $table->unsignedBigInteger('attribute_type_id');

            // The actual value (e.g., "Small", "Red", "Leather")
            $table->string('value', 255);

            // For color swatches (e.g., "#FF0000")
            $table->string('color_hex', 7)->nullable();

            // Controls display order in dropdowns
            $table->integer('sort_order')->default(0);

            // Image URL for the attribute value (optional)
            $table->string('image_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FK with cascade delete
            $table->foreign('attribute_type_id')->references('id')->on('variant_attribute_types')->onDelete('cascade');

            // Unique value per attribute type
            $table->unique(['attribute_type_id', 'value'], 'unq_attribute_type_value');

            // Indexes
            $table->index(['attribute_type_id'], 'idx_attribute_values_type');
            $table->index(['sort_order'], 'idx_attribute_values_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_attribute_values');
    }
};