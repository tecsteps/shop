<?php

namespace App\Enums;

enum CheckoutStatus: string
{
    case Started = 'started';
    case Addressed = 'addressed';
    case ShippingSelected = 'shipping_selected';
    case PaymentPending = 'payment_pending';
    case Completed = 'completed';
    case Expired = 'expired';
}
