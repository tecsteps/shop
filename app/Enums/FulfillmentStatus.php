<?php

namespace App\Enums;

/**
 * Order-level fulfillment status (orders.fulfillment_status).
 */
enum FulfillmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Partial = 'partial';
    case Fulfilled = 'fulfilled';
}
