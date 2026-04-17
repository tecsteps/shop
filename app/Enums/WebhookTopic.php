<?php

namespace App\Enums;

enum WebhookTopic: string
{
    case OrderCreated = 'order.created';
    case OrderPaid = 'order.paid';
    case OrderFulfilled = 'order.fulfilled';
    case OrderCancelled = 'order.cancelled';
    case OrderRefunded = 'order.refunded';
    case FulfillmentCreated = 'fulfillment.created';
    case FulfillmentShipped = 'fulfillment.shipped';
    case CustomerCreated = 'customer.created';
    case ProductCreated = 'product.created';
    case ProductUpdated = 'product.updated';
    case ProductDeleted = 'product.deleted';
    case CheckoutCompleted = 'checkout.completed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
