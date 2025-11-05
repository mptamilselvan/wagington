<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackorderResolutionService;

class ProcessBackorders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backorder:process {variant_id? : The variant ID to process backorders for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually process backorders for a specific variant or check all backordered orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $variantId = $this->argument('variant_id');
        $backorderService = app(BackorderResolutionService::class);

        if ($variantId) {
            $this->info("Processing backorders for variant ID: {$variantId}");
            
            try {
                $result = $backorderService->autoProcessBackordersForVariant((int)$variantId);
                
                $this->info("Result: " . json_encode($result, JSON_PRETTY_PRINT));
                
                // Validate result structure
                if (!is_array($result)) {
                    $this->warn("Invalid result format returned from service");
                    return 1;
                }

                // Check success status with safe default
                if (!empty($result['success'])) {
                    // Print stock usage with safe default
                    $stockUsed = $result['stock_used'] ?? 0;
                    $this->info("Stock used: {$stockUsed}");
                    
                    // Validate orders_processed array
                    $ordersProcessed = $result['orders_processed'] ?? [];
                    if (!is_array($ordersProcessed)) {
                        $this->warn("Invalid orders_processed format in result");
                        return 1;
                    }
                    
                    $this->info("Orders processed: " . count($ordersProcessed));
                    
                    // Only process orders if we have any
                    if (!empty($ordersProcessed)) {
                        foreach ($ordersProcessed as $order) {
                            if (!isset($order['order_number'], $order['status'])) {
                                $this->warn("Invalid order data format, skipping");
                                continue;
                            }
                            $this->info("  - Order #{$order['order_number']} moved to {$order['status']}");
                        }
                    }
                } else {
                    $message = $result['message'] ?? 'No reason provided';
                    $this->warn("No backorders processed: {$message}");
                }
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
                $this->error("Trace: " . $e->getTraceAsString());
                return 1;
            }
        } else {
            $this->info("Getting all backordered orders with stock info...");
            
            $ordersWithInfo = $backorderService->getBackorderedOrdersWithStockInfo();
            
            if (empty($ordersWithInfo)) {
                $this->info("No backordered orders found.");
                return 0;
            }
            
            $this->info("Found " . count($ordersWithInfo) . " backordered order(s):");
            
            foreach ($ordersWithInfo as $orderInfo) {
                $order = $orderInfo['order'];
                $stockInfo = $orderInfo['stock_info'];
                $isReady = $orderInfo['is_ready_to_process'];
                
                $this->info("\n" . str_repeat('-', 50));
                $this->info("Order #{$order->order_number} (ID: {$order->id})");
                $this->info("Status: {$order->status}");
                $this->info("Customer: " . ($order->user?->name ?? 'N/A'));
                $this->info("Ready to process: " . ($isReady ? 'YES ✓' : 'NO ✗'));
                
                if (!empty($stockInfo['items']) && is_array($stockInfo['items'])) {
                    $this->info("\nItems:");
                    foreach ($stockInfo['items'] as $index => $item) {
                        // Validate required item structure
                        $requiredKeys = [
                            'product_name',
                            'variant_display_name',
                            'quantity',
                            'reserved_quantity',
                            'needed',
                            'available'
                        ];

                        // Check if item is properly structured
                        if (!is_array($item) || array_diff($requiredKeys, array_keys($item))) {
                            $this->warn("    Skipping malformed item at index {$index}");
                            continue;
                        }

                        // Get status with proper boolean casting
                        $isReady = isset($item['is_ready']) ? (bool)$item['is_ready'] : false;
                        $status = $isReady ? '✓' : '✗';

                        // Access data with safe defaults
                        $productName = $item['product_name'] ?? 'Unknown Product';
                        $variantName = $item['variant_display_name'] ?? 'Unknown Variant';
                        $quantity = $item['quantity'] ?? 0;
                        $reserved = $item['reserved_quantity'] ?? 0;
                        $needed = $item['needed'] ?? 0;
                        $available = $item['available'] ?? 0;

                        $this->info("  {$status} {$productName} ({$variantName})");
                        $this->info("     Qty: {$quantity}, Reserved: {$reserved}, Needed: {$needed}, Available: {$available}");
                    }
                }
            }
            
            $this->info("\n" . str_repeat('-', 50));
        }
        
        return 0;
    }
}
