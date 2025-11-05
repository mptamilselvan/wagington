<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderShipped extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;
    public $trackingNumber;
    public $carrier;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, ?string $trackingNumber = null, ?string $carrier = null)
    {
        $this->order = $order;
        $this->user = $order->user;
        $this->trackingNumber = $trackingNumber ?? $order->tracking_number;
        $this->carrier = $carrier ?? $order->shipping_method;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Order #' . $this->order->order_number . ' Has Been Shipped!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order_shipped',
            with: [
                'order' => $this->order,
                'user' => $this->user,
                'trackingNumber' => $this->trackingNumber,
                'carrier' => $this->carrier,
                'orderUrl' => route('customer.order-detail', $this->order->order_number),
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
        return [];
    }
}
