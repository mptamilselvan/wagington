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

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->user = $order->user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Decide subject based on whether an invoice PDF is available to attach
        $payment = $this->order->payments->sortByDesc('id')->first();
        $hasInvoice = false;
        if ($payment && $payment->invoice_pdf_url) {
            // invoice_pdf_url may be an application route or a storage path; only mark as attached when a local file exists
            $path = public_path($payment->invoice_pdf_url);
            if (file_exists($path)) {
                $hasInvoice = true;
            }
        }

        $subject = 'Success! Your Order #' . $this->order->order_number . ' is Confirmed!';
        if ($hasInvoice) {
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
        // Attach invoice PDF if available
        $attachments = [];
        $payment = $this->order->payments->sortByDesc('id')->first();
        
        if ($payment && $payment->invoice_pdf_url && file_exists(public_path($payment->invoice_pdf_url))) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromPath(public_path($payment->invoice_pdf_url))
                ->as('invoice-' . $this->order->order_number . '.pdf')
                ->withMime('application/pdf');
        }
        
        return $attachments;
    }
}