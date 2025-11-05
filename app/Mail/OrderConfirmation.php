<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;
    private ?string $invoicePath = null;
    private bool $hasInvoice = false;
    private $latestPayment;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->user = $order->user;
        $this->detectInvoice();
    }

    /**
     * Detect and store invoice information for consistent access.
     */
    private function detectInvoice(): void
    {
        $payment = $this->getLatestPayment();
        if ($payment && $payment->invoice_pdf_url) {
            // Assuming invoice_pdf_url is relative to storage/app
            $path = storage_path('app/' . $payment->invoice_pdf_url);
            if (file_exists($path)) {
                $this->invoicePath = $path;
                $this->hasInvoice = true;
            }
        }
    }

    /**
     * Get the latest payment for the order, cached to avoid multiple queries.
     */
    private function getLatestPayment()
    {
        if ($this->latestPayment === null) {
            $this->latestPayment = $this->order->payments()->orderBy('id', 'desc')->first();
        }
        return $this->latestPayment;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Success! Your Order #' . $this->order->order_number . ' is Confirmed!';
        if ($this->hasInvoice) {
            $subject .= ' (Invoice Attached)';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Prefer the tax rate that was applied when the order was placed. Fall back to current tax service rate.
        $taxRate = $this->order->applied_tax_rate ?? app(\App\Services\TaxService::class)->getCurrentTaxRate();
        
        return new Content(
            view: 'emails.order_confirmation',
            with: [
                'order' => $this->order,
                'user' => $this->user,
                'taxRate' => $taxRate,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        
        // Double-check file still exists at time of attachment
        if ($this->hasInvoice && $this->invoicePath && file_exists($this->invoicePath)) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromPath($this->invoicePath)
                ->as('invoice-' . $this->order->order_number . '.pdf')
                ->withMime('application/pdf');
        } else if ($this->hasInvoice && $this->invoicePath) {
            // Log warning if file disappeared after detection
            \Log::warning('Invoice file missing at send time', [
                'order_id' => $this->order->id,
                'expected_path' => $this->invoicePath
            ]);
        }
        
        return $attachments;
    }
}