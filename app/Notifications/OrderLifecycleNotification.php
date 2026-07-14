<?php

namespace App\Notifications;

use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\Refund;
use App\Services\OrderStatusLink;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrderLifecycleNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly string $type,
        public readonly Fulfillment|Refund|string|null $context = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject($this->subject())->greeting('Hello!');

        match ($this->type) {
            'confirmed' => $message
                ->line("We received order {$this->order->order_number}.")
                ->line('Total: '.number_format($this->order->total_amount / 100, 2).' '.$this->order->currency),
            'refunded' => $message
                ->line("A refund was processed for order {$this->order->order_number}.")
                ->line('Refund amount: '.number_format(($this->context instanceof Refund ? $this->context->amount : 0) / 100, 2).' '.$this->order->currency),
            'shipped' => $message
                ->line("Order {$this->order->order_number} has shipped.")
                ->line($this->context instanceof Fulfillment && $this->context->tracking_number
                    ? "Tracking: {$this->context->tracking_number}"
                    : 'Tracking details will follow when available.'),
            'cancelled' => $message
                ->line("Order {$this->order->order_number} was cancelled.")
                ->line(is_string($this->context) && $this->context !== '' ? "Reason: {$this->context}" : 'No payment was captured.'),
            default => $message->line("Order {$this->order->order_number} was updated."),
        };

        return $message->action('View order status', app(OrderStatusLink::class)->url($this->order))
            ->line('Thank you for shopping with us.');
    }

    private function subject(): string
    {
        return match ($this->type) {
            'confirmed' => "Order {$this->order->order_number} confirmed",
            'refunded' => "Refund for {$this->order->order_number}",
            'shipped' => "Order {$this->order->order_number} shipped",
            'cancelled' => "Order {$this->order->order_number} cancelled",
            default => "Order {$this->order->order_number} update",
        };
    }
}
