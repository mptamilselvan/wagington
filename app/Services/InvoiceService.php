<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    // Storage configuration constants
    private const DISK_DIGITALOCEAN = 'do_spaces';
    private const DIR_INVOICES = 'invoices';
    
    /**
     * Generate an invoice PDF for an order
     *
     * @param Order $order
     * @return array ['status', 'message', 'pdfBytes', 'invoice_number']
     */
    public function generateInvoiceForOrder(Order $order): array
    {
        try {
            // Validate order has required data
            if (!$order->id || !$order->order_number) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid order data: missing order ID or number'
                ];
            }
            
            // Generate a unique invoice number
            $invoiceNumber = $this->generateInvoiceNumber($order);
            
            // Create the PDF
            $pdf = new Fpdi();
            $pdf->SetCreator('Wagington E-commerce');
            $pdf->SetAuthor('Wagington');
            $pdf->SetTitle('Invoice ' . $invoiceNumber);
            $pdf->SetMargins(15, 15, 15);
            $pdf->AddPage();
            
            // Add header
            $this->addHeader($pdf, $invoiceNumber, $order);
            
            // Add billing/shipping information
            $this->addCustomerInformation($pdf, $order);
            
            // Add order items
            $this->addOrderItems($pdf, $order);
            
            // Add order summary
            $this->addOrderSummary($pdf, $order);
            
            // Add footer
            $this->addFooter($pdf);
            
            // Output PDF as string
            $pdfBytes = $pdf->Output('S');
            
            return [
                'status' => 'success',
                'message' => 'Invoice generated successfully',
                'pdfBytes' => $pdfBytes,
                'invoice_number' => $invoiceNumber
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice PDF', [
                'order_id' => $order->id ?? 'unknown',
                'order_number' => $order->order_number ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'status' => 'error',
                'message' => 'Failed to generate invoice: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate a unique invoice number
     *
     * @param Order $order
     * @return string
     */
    private function generateInvoiceNumber(Order $order): string
    {
        // Format: INV-YYYYMMDD-HHMMSSmmm-ORDERID
        // Use high-resolution timestamp (hours/minutes/seconds + milliseconds) to avoid collisions
        // Keep order id padded to at least 4 digits but do not truncate larger ids.
        $orderIdPart = str_pad((string)$order->id, 4, '0', STR_PAD_LEFT);
        $now = now();
        // Get milliseconds from microtime
        $micro = (int) floor((microtime(true) - floor(microtime(true))) * 1000);
        $timePart = $now->format('His') . sprintf('%03d', $micro); // HHMMSSmmm

        return 'INV-' . $now->format('Ymd') . '-' . $timePart . '-' . $orderIdPart;
    }
    
    /**
     * Add header to the invoice
     *
     * @param Fpdi $pdf
     * @param string $invoiceNumber
     * @param Order $order
     */
    private function addHeader(Fpdi $pdf, string $invoiceNumber, Order $order): void
    {
        // Company header
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->Cell(0, 10, 'WAGINGTON', 0, 1, 'L');
        
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 5, '123 Pet Street, Singapore 123456', 0, 1, 'L');
        $pdf->Cell(0, 5, 'Email: info@Wagington.com.sg | Phone: +65 1234 5678', 0, 1, 'L');
        
        // Line break
        $pdf->Ln(10);
        
        // Invoice title and details
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'R');
        
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, 'Invoice #: ' . $invoiceNumber, 0, 1, 'R');
        $pdf->Cell(0, 6, 'Date: ' . $order->created_at->format('d M Y'), 0, 1, 'R');
        $pdf->Cell(0, 6, 'Order #: ' . $order->order_number, 0, 1, 'R');
        
        // Line break
        $pdf->Ln(10);
    }
    
    /**
     * Add customer information (billing/shipping)
     *
     * @param Fpdi $pdf
     * @param Order $order
     */
    private function addCustomerInformation(Fpdi $pdf, Order $order): void
    {
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Bill To:', 0, 1, 'L');
        
        $pdf->SetFont('Helvetica', '', 10);
        
        if ($order->billingAddress) {
            $billing = $order->billingAddress;
            
            // Combine first and last name, only adding space if both exist
            $firstName = $billing->first_name ?? '';
            $lastName = $billing->last_name ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
            $pdf->Cell(0, 5, $fullName ?: 'N/A', 0, 1, 'L');
            
            // Address line 1 is required for a valid address
            $pdf->Cell(0, 5, $billing->address_line1 ?? '', 0, 1, 'L');
            
            // Optional address line 2
            if (!empty($billing->address_line2)) {
                $pdf->Cell(0, 5, $billing->address_line2, 0, 1, 'L');
            }
            
            // Combine city and postal code, only adding separator if both exist
            $city = $billing->city ?? '';
            $postalCode = $billing->postal_code ?? '';
            $cityPostal = trim($city . ($city && $postalCode ? ', ' : '') . $postalCode);
            $pdf->Cell(0, 5, $cityPostal ?: '', 0, 1, 'L');
            
            // Country
            $pdf->Cell(0, 5, $billing->country ?? '', 0, 1, 'L');
        } else {
            $pdf->Cell(0, 5, 'N/A', 0, 1, 'L');
        }
        
        $pdf->Ln(5);
        
        // Shipping address (if different)
        if ($order->shippingAddress && $order->shippingAddress->id !== $order->billingAddress?->id) {
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Ship To:', 0, 1, 'L');
            
            $pdf->SetFont('Helvetica', '', 10);
            $shipping = $order->shippingAddress;
            if ($shipping) {
                $firstName = $shipping->first_name ?? '';
                $lastName = $shipping->last_name ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                $pdf->Cell(0, 5, $fullName ?: 'N/A', 0, 1, 'L');
                $pdf->Cell(0, 5, $shipping->address_line1 ?? '', 0, 1, 'L');
                
                // Add address line 2 if it exists
                if (!empty($shipping->address_line2)) {
                    $pdf->Cell(0, 5, $shipping->address_line2, 0, 1, 'L');
                }
                
                // City and postal code
                $city = $shipping->city ?? '';
                $postalCode = $shipping->postal_code ?? '';
                $cityPostal = trim($city . ($city && $postalCode ? ', ' : '') . $postalCode);
                $pdf->Cell(0, 5, $cityPostal ?: '', 0, 1, 'L');
                
                // Country
                $pdf->Cell(0, 5, $shipping->country ?? '', 0, 1, 'L');
            } else {
                $pdf->Cell(0, 5, 'N/A', 0, 1, 'L');
            }
            
            $pdf->Ln(5);
        }
    }
    
    /**
     * Add order items to the invoice
     *
     * @param Fpdi $pdf
     * @param Order $order
     */
    private function addOrderItems(Fpdi $pdf, Order $order): void
    {
        // Table header
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(80, 8, 'Item', 1, 0, 'L', true);
        $pdf->Cell(25, 8, 'Qty', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Unit Price', 1, 0, 'R', true);
        $pdf->Cell(35, 8, 'Total', 1, 1, 'R', true);
        
        // Table rows
        $pdf->SetFont('Helvetica', '', 9);
        
        // Check if order has items
        if ($order->items && $order->items->count() > 0) {
            foreach ($order->items as $item) {
                // Item name and variant
                $itemName = $item->product_name ?? 'Unknown Item';
                if (!empty($item->variant_display_name)) {
                    $itemName .= ' (' . $item->variant_display_name . ')';
                }
                
                // Add main item
                $pdf->Cell(80, 6, $itemName, 1, 0, 'L');
                $pdf->Cell(25, 6, $item->quantity ?? 0, 1, 0, 'C');
                $pdf->Cell(30, 6, '$' . number_format($item->unit_price ?? 0, 2), 1, 0, 'R');
                $pdf->Cell(35, 6, '$' . number_format($item->total_price ?? 0, 2), 1, 1, 'R');
                
                // Add addons if any
                if ($item->addons && $item->addons->count() > 0) {
                    foreach ($item->addons as $addon) {
                        $addonName = '  + ' . ($addon->addon_name ?? 'Unknown Addon');
                        $pdf->Cell(80, 6, $addonName, 1, 0, 'L');
                        $pdf->Cell(25, 6, $addon->quantity ?? 0, 1, 0, 'C');
                        $pdf->Cell(30, 6, '$' . number_format($addon->unit_price ?? 0, 2), 1, 0, 'R');
                        $pdf->Cell(35, 6, '$' . number_format($addon->total_price ?? 0, 2), 1, 1, 'R');
                    }
                }
            }
        } else {
            // No items found
            $pdf->Cell(80, 6, 'No items found', 1, 0, 'L');
            $pdf->Cell(25, 6, '0', 1, 0, 'C');
            $pdf->Cell(30, 6, '$0.00', 1, 0, 'R');
            $pdf->Cell(35, 6, '$0.00', 1, 1, 'R');
        }
        
        $pdf->Ln(5);
    }
    
    /**
     * Add order summary with totals
     *
     * @param Fpdi $pdf
     * @param Order $order
     */
    private function addOrderSummary(Fpdi $pdf, Order $order): void
    {
        // Align to the right for summary
        $pdf->SetX(130);
        
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(30, 6, 'Subtotal:', 1, 0, 'L');
        $pdf->Cell(35, 6, '$' . number_format($order->subtotal ?? 0, 2), 1, 1, 'R');
        
        // Discount if applicable
        $discountAmount = $order->coupon_discount_amount ?? 0;
        if ($discountAmount > 0) {
            $pdf->SetX(130);
            $pdf->SetTextColor(0, 128, 0); // Green color for discount
            $pdf->Cell(30, 6, 'Discount:', 1, 0, 'L');
            $pdf->Cell(35, 6, '-$' . number_format($discountAmount, 2), 1, 1, 'R');
            $pdf->SetTextColor(0, 0, 0); // Reset to black
        }
        
        // Tax - Use the tax rate that was applied when the order was placed
        $taxAmount = $order->tax_amount ?? 0;
        // Use the applied_tax_rate from the order model, fallback to calculated rate if not available
        $taxRate = $order->applied_tax_rate ?? (($order->subtotal > 0) ? ($taxAmount / $order->subtotal) * 100 : 18.0);
        
        $pdf->SetX(130);
        $pdf->Cell(30, 6, 'GST (' . number_format($taxRate, 2) . '%):', 1, 0, 'L');
        $pdf->Cell(35, 6, '$' . number_format($taxAmount, 2), 1, 1, 'R');
        
        // Shipping
        $pdf->SetX(130);
        $pdf->Cell(30, 6, 'Shipping:', 1, 0, 'L');
        $pdf->Cell(35, 6, '$' . number_format($order->shipping_amount ?? 0, 2), 1, 1, 'R');
        
        // Line break
        $pdf->Ln(2);
        
        // Total
        $pdf->SetX(130);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->Cell(30, 8, 'Total:', 1, 0, 'L');
        $pdf->Cell(35, 8, '$' . number_format($order->total_amount ?? 0, 2), 1, 1, 'R');
    }
    
    /**
     * Add footer to the invoice
     *
     * @param Fpdi $pdf
     */
    private function addFooter(Fpdi $pdf): void
    {
        $pdf->SetY(-30);
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Thank you for your business!', 0, 1, 'C');
        $pdf->Cell(0, 5, 'This is a computer generated invoice. No signature required.', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }
    
    /**
     * Save invoice PDF to storage and update payment record
     *
     * @param Order $order
     * @param Payment $payment
     * @return bool
     */
    public function saveInvoiceAndUpdatePayment(Order $order, Payment $payment): bool
    {
        try {
            // Validate inputs
            if (!$order->id || !$payment->id) {
                Log::error('Invalid order or payment data for invoice generation', [
                    'order_id' => $order->id ?? 'missing',
                    'payment_id' => $payment->id ?? 'missing'
                ]);
                return false;
            }
            
            // Generate the invoice
            $result = $this->generateInvoiceForOrder($order);
            
            if ($result['status'] !== 'success') {
                Log::error('Failed to generate invoice for order', [
                    'order_id' => $order->id,
                    'error' => $result['message']
                ]);
                return false;
            }
            
            // Save PDF to storage
            $invoiceNumber = $result['invoice_number'];

            // Determine which disk to use (Digital Ocean Spaces if configured, otherwise local)
            $disk = $this->getPreferredDisk();

            // Build a safe filename and avoid overwriting existing files by appending a timestamp if needed
            $baseName = $invoiceNumber;
            $filename = self::DIR_INVOICES . '/' . $baseName . '.pdf';
            if (Storage::disk($disk)->exists($filename)) {
                $filename = self::DIR_INVOICES . '/' . $baseName . '-' . now()->format('YmdHis') . '.pdf';
            }

            // Attempt to store the file first
            $stored = Storage::disk($disk)->put($filename, $result['pdfBytes']);
            if (!$stored) {
                Log::error('Failed to store invoice PDF to disk', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'disk' => $disk,
                    'filename' => $filename,
                ]);
                return false;
            }

            // Generate public viewer URL (from storage) and a direct-download route for PDFs
            $invoiceUrl = Storage::disk($disk)->url($filename);
            // Prefer application-level download route for direct PDF download (keeps control and auth)
            try {
                $invoicePdfUrl = route('customer.invoice.download', ['orderNumber' => $order->order_number, 'paymentId' => $payment->id]);
            } catch (\Throwable $e) {
                Log::warning('Failed to generate invoice download route, using storage URL as fallback', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage()
                ]);
                // Fallback to storage URL if route generation fails
                $invoicePdfUrl = $invoiceUrl;
            }

            // Update payment record inside a DB transaction. If DB update fails, remove stored file to avoid orphaned files.
            DB::beginTransaction();
            try {
                // Use invoice_number as the human-facing identifier. Do not overwrite existing invoice_id unless necessary.
                $payment->update([
                    'invoice_number' => $invoiceNumber,
                    'invoice_url' => $invoiceUrl,
                    'invoice_pdf_url' => $invoicePdfUrl,
                ]);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                // Attempt cleanup of the stored file since DB update failed
                try {
                    Storage::disk($disk)->delete($filename);
                } catch (\Throwable $_) {
                    Log::warning('Failed to delete invoice file after DB rollback', [
                        'filename' => $filename,
                        'disk' => $disk,
                    ]);
                }

                Log::error('Failed to update payment with invoice metadata, rolled back and deleted stored file', [
                    'order_id' => $order->id,
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return false;
            }
            
            Log::info('Invoice generated and payment updated', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'invoice_number' => $invoiceNumber,
                'invoice_url' => $invoiceUrl,
                'disk' => $disk
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to save invoice and update payment', [
                'order_id' => $order->id ?? 'unknown',
                'payment_id' => $payment->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get the preferred storage disk for invoices
     * 
     * @return string
     */
    private function getPreferredDisk(): string
    {
        // Check if Digital Ocean Spaces is enabled via config (works with config:cache)
        if (config('filesystems.use_digitalocean_spaces', false)) {
            return self::DISK_DIGITALOCEAN;
        }
        
        // Fallback to public disk
        return 'public';
    }
}
