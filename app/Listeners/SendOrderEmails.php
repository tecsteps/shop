<?php

namespace App\Listeners;

use App\Events\FulfillmentShipped;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderRefunded;
use App\Mail\OrderCancelledMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderRefundedMail;
use App\Mail\OrderShippedMail;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the customer-facing order lifecycle emails (spec 05 §17):
 * confirmation on OrderCreated, shipping notification on
 * FulfillmentShipped, cancellation on OrderCancelled and refund
 * notification on OrderRefunded.
 *
 * Failure-safe: order events fire inside the checkout/fulfillment
 * transactions, so every send is wrapped — a broken mailer must never
 * break commerce. Failures are reported to the exception handler.
 */
class SendOrderEmails
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        [$recipient, $mailable] = match (true) {
            $event instanceof OrderCreated => [
                $event->order->email,
                new OrderConfirmationMail($event->order),
            ],
            $event instanceof FulfillmentShipped => [
                $event->fulfillment->order->email,
                new OrderShippedMail($event->fulfillment->order, $event->fulfillment),
            ],
            $event instanceof OrderCancelled => [
                $event->order->email,
                new OrderCancelledMail($event->order, $event->reason),
            ],
            $event instanceof OrderRefunded => [
                $event->order->email,
                new OrderRefundedMail($event->order, $event->refund),
            ],
            default => [null, null],
        };

        if ($recipient === null || $mailable === null) {
            return;
        }

        $this->sendSafely($recipient, $mailable);
    }

    /**
     * Send the mailable, reporting (not propagating) any failure.
     */
    private function sendSafely(string $recipient, Mailable $mailable): void
    {
        try {
            Mail::to($recipient)->send($mailable);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
