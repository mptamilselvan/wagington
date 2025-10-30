<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment;

class InvoiceController extends Controller
{
    /**
     * Download invoice PDF for an order (web version)
     * 
     * @param string $orderNumber
     * @param string $paymentId
     * @return \Illuminate\Http\Response
     */
    public function downloadInvoice(string $orderNumber, string $paymentId)
    {
        try {
            // This route is already protected by auth.customer middleware, so user is authenticated
            
            // Find the payment record
            $payment = Payment::find($paymentId);
            
            if (!$payment) {
                return response()->json(['status' => 'error', 'message' => 'Payment record not found'], 404);
            }
            
            // Ensure the user owns this payment
            $order = $payment->order;
            if (!$order || $order->order_number !== $orderNumber || $order->user_id !== Auth::id()) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized access to invoice'], 403);
            }
            
            // Check if we have an invoice number (which indicates the invoice exists)
            if (!$payment->invoice_number) {
                return response()->json(['status' => 'error', 'message' => 'Invoice not available for this payment'], 404);
            }
            
            // Determine which disk was used to store the invoice
            $disk = config('filesystems.use_digitalocean_spaces', false) ? 'do_spaces' : 'public';
            
            // Build the filename from the invoice number
            $filename = 'invoices/' . $payment->invoice_number . '.pdf';
            
            // Check if the file exists
            if (!Storage::disk($disk)->exists($filename)) {
                // Try with timestamp suffix if the base filename doesn't exist
                $files = Storage::disk($disk)->files('invoices');
                $matchingFile = null;
                $escapedInvoiceNumber = preg_quote($payment->invoice_number, '/');
                $pattern = '/^' . $escapedInvoiceNumber . '(?:[^A-Za-z0-9]|\.pdf)/';
                
                foreach ($files as $file) {
                    $basename = basename($file);
                    if (preg_match($pattern, $basename)) {
                        $matchingFile = $file;
                        break;
                    }
                }
                
                if (!$matchingFile) {
                    return response()->json(['status' => 'error', 'message' => 'Invoice file not found in storage'], 404);
                }
                
                $filename = $matchingFile;
            }
            
            // Get the file contents
            $fileContents = Storage::disk($disk)->get($filename);
            
            // Return the PDF with proper headers for download
            $safeOrderNumber = preg_replace('/[^a-zA-Z0-9_-]/', '', $order->order_number);
            return response($fileContents)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="invoice-' . $safeOrderNumber . '.pdf"')
                ->header('Content-Length', strlen($fileContents));
                
        } catch (\Exception $e) {
            \Log::error('Failed to download invoice', [
                'order_number' => $orderNumber,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return JSON response for API requests or redirect back with error for web requests
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Failed to download invoice. Please try again later.'], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to download invoice. Please try again later.');
        }
    }
}