<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'partially_delivered' status to orders table
     * This will be used when only some shippable items have been delivered
     */
    public function up(): void
    {
        // PostgreSQL uses a check constraint with the status column
        // First, drop the existing constraint
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        
        // Add the new constraint with the additional 'partially_delivered' value
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'confirmed'::character varying, 'processing'::character varying, 'partially_shipped'::character varying, 'partially_backordered'::character varying, 'backordered'::character varying, 'shipped'::character varying, 'delivered'::character varying, 'partially_delivered'::character varying, 'completed'::character varying, 'cancelled'::character varying, 'refunded'::character varying, 'payment_failed'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change 'partially_delivered' statuses to 'delivered'
        DB::table('orders')
            ->where('status', 'partially_delivered')
            ->update(['status' => 'delivered']);
            
        // Drop the new constraint
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        
        // Restore the original constraint without 'partially_delivered'
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'confirmed'::character varying, 'processing'::character varying, 'partially_shipped'::character varying, 'partially_backordered'::character varying, 'backordered'::character varying, 'shipped'::character varying, 'delivered'::character varying, 'completed'::character varying, 'cancelled'::character varying, 'refunded'::character varying, 'payment_failed'::character varying]::text[]))");
    }
};
