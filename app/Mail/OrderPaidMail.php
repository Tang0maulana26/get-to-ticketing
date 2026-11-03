<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\TicketPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $eventTitle = $this->order->items->first()?->event_title ?? 'Concert Event';

        return new Envelope(
            subject: "E-Ticket & Bukti Pembayaran: {$eventTitle} [#{$this->order->number}]",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-paid',
            with: [
                'order' => $this->order->loadMissing(['items.tickets', 'user']),
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
        return [
            Attachment::fromData(
                function () {
                    try {
                        return app(TicketPdfService::class)->output($this->order);
                    } catch (\Throwable $e) {
                        report($e);

                        return '';
                    }
                },
                "E-Ticket-{$this->order->number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}

