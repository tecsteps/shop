<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a fulfillment is attempted on an order whose financial status is
 * not `paid` or `partially_refunded`, or when a requested quantity exceeds the
 * unfulfilled quantity of an order line.
 */
class FulfillmentGuardException extends RuntimeException
{
    //
}
