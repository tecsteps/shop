<?php

namespace App\Exceptions;

use Exception;

class InvalidShippingRateException extends Exception
{
    public static function notApplicable(int $rateId): self
    {
        return new self("Shipping rate {$rateId} is not available for the checkout address.");
    }
}
