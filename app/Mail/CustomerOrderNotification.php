<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerOrderNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public const ORDER_CONFIRMATION = 'order_confirmation';

    public const REFUND = 'refund';

    public const SHIPPED = 'shipped';

    public const CANCELLED = 'cancelled';

    /** @param array<string, mixed> $details */
    public function __construct(
        public Order $order,
        public string $notificationType,
        public array $details = [],
    ) {
        $this->order->loadMissing('lines');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectForNotification());
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.orders.customer-notification',
            with: [
                'order' => $this->order,
                'notificationType' => $this->notificationType,
                'details' => $this->details,
            ],
        );
    }

    private function subjectForNotification(): string
    {
        return match ($this->notificationType) {
            self::ORDER_CONFIRMATION => "Order {$this->order->order_number} confirmed",
            self::REFUND => "Refund processed for order {$this->order->order_number}",
            self::SHIPPED => "Order {$this->order->order_number} has shipped",
            self::CANCELLED => "Order {$this->order->order_number} cancelled",
            default => "Update for order {$this->order->order_number}",
        };
    }
}
