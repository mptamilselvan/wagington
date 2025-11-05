<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\User;
use InvalidArgumentException;

class BackorderResolved extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;

    /**
     * Create a new message instance.
     *
     * Note: The Order's user relationship doesn't need to be eager-loaded 
     * as we handle null users gracefully in the email template.
     * The template will display "Customer" as a fallback if no user is attached.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        
        // Safely handle the user relationship
        $this->user = $order->user instanceof User 
            ? $order->user 
            : new User(['name' => 'Customer']); // Guest placeholder
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Great News! Your Order #' . $this->order->order_number . ' is Ready to Ship',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.backorder_resolved',
            with: [
                'order' => $this->order,
                'user' => $this->user,
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
