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
        // Drop the old unique constraint that includes product_id
        DB::statement('ALTER TABLE guest_cart_items DROP CONSTRAINT IF EXISTS uq_guest_cart_items_session_product_variant');
        
        // Create new unique constraint with only session_token and variant_id
        DB::statement('ALTER TABLE guest_cart_items ADD CONSTRAINT uq_guest_cart_items_session_variant UNIQUE (session_token, variant_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original constraint
        DB::statement('ALTER TABLE guest_cart_items DROP CONSTRAINT IF EXISTS uq_guest_cart_items_session_variant');
        
        DB::statement('ALTER TABLE guest_cart_items ADD CONSTRAINT uq_guest_cart_items_session_product_variant UNIQUE (session_token, product_id, variant_id)');
    }
};
