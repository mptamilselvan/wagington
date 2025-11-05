<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;

class GenerateMissingInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-missing {--order-number= : Specific order number to generate invoice for} {--force : Force regeneration even if invoice exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate invoices for orders that are missing them';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceService $invoiceService)
    {
        $orderNumber = $this->option('order-number');
        $force = $this->option('force');
        
        if ($orderNumber) {
            // Generate invoice for a specific order
            $this->info("Generating invoice for order: {$orderNumber}");

            // Eager-load payments for the specific order so generateInvoiceForOrder
            // doesn't issue an extra query when accessing $order->payments.
            $order = Order::where('order_number', $orderNumber)
                ->with(['payments' => function ($query) use ($force) {
                    $query->where('status', 'succeeded');

                    if (!$force) {
                        // Only eager load payments that need invoices when not forcing
                        $query->where(function ($q) {
                            $q->whereNull('invoice_url')
                              ->orWhereNull('invoice_pdf_url')
                              ->orWhere('invoice_url', '')
                              ->orWhere('invoice_pdf_url', '');
                        });
                    }
                }])->first();

            if (!$order) {
                $this->error("Order not found: {$orderNumber}");
                return 1;
            }
            
            $success = $this->generateInvoiceForOrder($invoiceService, $order, $force);
            return $success ? 0 : 1;
        } else {
            // Generate invoices for all orders missing invoices
            $this->info('Generating invoices for all orders missing them...');
            
            // Find orders with successful payments but missing invoice information
            // Reusable predicate for payments that are missing invoice data
            $force = $force; // ensure $force is in scope for the closures below (kept for clarity)
            $invoiceMissingFilter = function ($q) {
                $q->whereNull('invoice_url')
                  ->orWhereNull('invoice_pdf_url')
                  ->orWhere('invoice_url', '')
                  ->orWhere('invoice_pdf_url', '');
            };

            $query = Order::whereHas('payments', function ($query) use ($invoiceMissingFilter, $force) {
                $query->where('status', 'succeeded');

                if (!$force) {
                    // Only look for payments missing invoice info when not forcing
                    $query->where($invoiceMissingFilter);
                }
            })->with(['payments' => function ($query) use ($invoiceMissingFilter, $force) {
                $query->where('status', 'succeeded');

                if (!$force) {
                    // Only eager load payments that need invoices
                    $query->where($invoiceMissingFilter);
                }
            }]);
            
            $orders = $query->get();
            
            if ($orders->isEmpty()) {
                $this->info('No orders found missing invoices.');
                return 0;
            }
            
            $this->info("Found {$orders->count()} orders " . ($force ? 'to process' : 'missing invoices') . ".");
            
            $progressBar = $this->output->createProgressBar($orders->count());
            $progressBar->start();
            
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($orders as $order) {
                try {
                    if ($this->generateInvoiceForOrder($invoiceService, $order, $force)) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Failed to generate invoice for order', [
                        'order_number' => $order->order_number,
                        'error' => $e->getMessage()
                    ]);
                    $this->error("Error with order {$order->order_number}: " . $e->getMessage());
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine(2);
            
            $this->info("Successfully generated invoices: {$successCount}");
            $this->info("Failed to generate invoices: {$errorCount}");
        }
        
        return 0;
    }
    
    /**
     * Generate invoice for a specific order
     *
     * @param InvoiceService $invoiceService
     * @param Order $order
     * @param bool $force
     * @return bool
     */
    private function generateInvoiceForOrder(InvoiceService $invoiceService, Order $order, bool $force = false): bool
    {
        // Find successful payment that needs an invoice
        $payment = $order->payments
            ->where('status', 'succeeded')
            ->when(!$force, function ($payments) {
                return $payments->filter(function ($payment) {
                    return empty($payment->invoice_url) || 
                           empty($payment->invoice_pdf_url);
                });
            })
            ->first();
        
        if (!$payment) {
            if ($force) {
                $this->warn("No successful payment found for order: {$order->order_number}");
            } else {
                $this->info("No payments needing invoice found for order: {$order->order_number}");
            }
            return false;
        }
        
        if ($force) {
            $this->info("Force regenerating invoice for order: {$order->order_number}");
        }
        
        try {
            $result = $invoiceService->saveInvoiceAndUpdatePayment($order, $payment);
            
            if ($result) {
                $this->info("Successfully generated invoice for order: {$order->order_number}");
                return true;
            } else {
                $this->error("Failed to generate invoice for order: {$order->order_number}");
                return false;
            }
        } catch (\Exception $e) {
            $this->error("Error generating invoice for order {$order->order_number}: " . $e->getMessage());
            Log::error('Failed to generate invoice for order', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}