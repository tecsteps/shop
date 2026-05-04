<?php

namespace App\Enums;

enum WebhookEventType: string
{
    case OrderCreated = 'order.created';
    case OrderPaid = 'order.paid';
    case OrderUpdated = 'order.updated';
    case OrderCancelled = 'order.cancelled';
    case OrderFulfilled = 'order.fulfilled';
    case OrderRefunded = 'order.refunded';
    case ProductCreated = 'product.created';
    case ProductUpdated = 'product.updated';
    case ProductDeleted = 'product.deleted';
    case CustomerCreated = 'customer.created';
    case CheckoutCompleted = 'checkout.completed';
    case FulfillmentCreated = 'fulfillment.created';
    case RefundCreated = 'refund.created';

    public function label(): string
    {
        return match ($this) {
            self::OrderCreated => 'Order created',
            self::OrderPaid => 'Order paid',
            self::OrderUpdated => 'Order updated',
            self::OrderCancelled => 'Order cancelled',
            self::OrderFulfilled => 'Order fulfilled',
            self::OrderRefunded => 'Order refunded',
            self::ProductCreated => 'Product created',
            self::ProductUpdated => 'Product updated',
            self::ProductDeleted => 'Product deleted',
            self::CustomerCreated => 'Customer created',
            self::CheckoutCompleted => 'Checkout completed',
            self::FulfillmentCreated => 'Fulfillment created',
            self::RefundCreated => 'Refund created',
        };
    }

    /**
     * @return list<self>
     */
    public static function selectable(): array
    {
        return [
            self::OrderCreated,
            self::OrderUpdated,
            self::OrderCancelled,
            self::ProductCreated,
            self::ProductUpdated,
            self::ProductDeleted,
            self::CustomerCreated,
            self::CheckoutCompleted,
            self::FulfillmentCreated,
            self::RefundCreated,
        ];
    }
}
