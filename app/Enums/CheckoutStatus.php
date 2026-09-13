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
}
