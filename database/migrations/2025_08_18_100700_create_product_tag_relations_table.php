<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Table: product_tag_relations
     * Purpose: Establishes many-to-many relationships between products and tags,
     * allowing products to have multiple tags and tags to be applied to multiple products.
     * Note: Comments are kept in this file only (no DB-level comments), as requested.
     */
    public function up(): void
    {
        Schema::create('product_tag_relations', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Reference to the product
            $table->unsignedBigInteger('product_id');

            // Reference to the tag
            $table->unsignedBigInteger('tag_id');

            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            // FKs with cascade delete behavior
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('product_tags')->onDelete('cascade');

        });

        DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS unq_product_tag ON product_tag_relations (product_id, tag_id) WHERE deleted_at IS NULL;");
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tag_relations');
    }
};