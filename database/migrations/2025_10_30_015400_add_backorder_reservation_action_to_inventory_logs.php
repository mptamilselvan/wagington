<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Purpose: Add 'backorder_reservation' action type to inventory_logs table
     * This action is used when stock is automatically reserved for backordered items when new stock arrives
     */
    public function up(): void
    {
        // Drop the existing check constraint
        DB::statement('ALTER TABLE inventory_logs DROP CONSTRAINT IF EXISTS inventory_logs_action_check');
        
        // Recreate the constraint with backorder_reservation included
        DB::statement("
            ALTER TABLE inventory_logs 
            ADD CONSTRAINT inventory_logs_action_check 
            CHECK (action IN (
                'stock_in', 
                'stock_out', 
                'adjustment', 
                'sale', 
                'return', 
                'damaged', 
                'sale_backorder', 
                'shipment', 
                'backorder_reservation'
            ))
        ");
    }

    public function down(): void
    {
        // Drop the constraint
        DB::statement('ALTER TABLE inventory_logs DROP CONSTRAINT IF EXISTS inventory_logs_action_check');
        
        // Recreate the original constraint without backorder_reservation
        DB::statement("
            ALTER TABLE inventory_logs 
            ADD CONSTRAINT inventory_logs_action_check 
            CHECK (action IN (
                'stock_in', 
                'stock_out', 
                'adjustment', 
                'sale', 
                'return', 
                'damaged', 
                'sale_backorder', 
                'shipment'
            ))
        ");
    }
};
