<?php

namespace App\Enums;

enum CheckoutStatus: string
{
    case Started = 'started';
    case Addressed = 'addressed';
    case ShippingSelected = 'shipping_selected';
    case PaymentSelected = 'payment_selected';
    case Completed = 'completed';
    case Expired = 'expired';

    /**
     * Whether the checkout is still in progress and may expire.
     */
    public function isActive(): bool
    {
        return ! in_array($this, [self::Completed, self::Expired], true);
    }
}
