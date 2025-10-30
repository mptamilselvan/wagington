<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add 'completed' status to orders table
     * This will be used for orders with all non-shippable items handed over
     */
    public function up(): void
    {
        // PostgreSQL uses a check constraint with the status column
        // First, drop the existing constraint
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        
        // Add the new constraint with the additional 'completed' value
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'confirmed'::character varying, 'processing'::character varying, 'partially_shipped'::character varying, 'partially_backordered'::character varying, 'backordered'::character varying, 'shipped'::character varying, 'delivered'::character varying, 'completed'::character varying, 'cancelled'::character varying, 'refunded'::character varying, 'payment_failed'::character varying]::text[]))");
    }

    public function down(): void
    {
        // Change 'completed' statuses (the new status) to 'delivered' (the old final status).
        // This ensures no data violates the constraint being restored.
        DB::table('orders')
            ->where('status', 'completed')
            ->update(['status' => 'delivered']);
            
        // Drop the new constraint
        DB::statement("ALTER TABLE orders DROP CONSTRAINT IF EXISTS orders_status_check");
        
        // Restore the original constraint without 'completed'
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'confirmed'::character varying, 'processing'::character varying, 'partially_shipped'::character varying, 'partially_backordered'::character varying, 'backordered'::character varying, 'shipped'::character varying, 'delivered'::character varying, 'cancelled'::character varying, 'refunded'::character varying, 'payment_failed'::character varying]::text[]))");
    }
};

