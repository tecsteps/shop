<?php

namespace App\Enums;

enum FulfillmentOrderStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Partial = 'partial';
    case Fulfilled = 'fulfilled';
}
