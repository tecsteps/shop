<?php

namespace App\Exceptions;

use RuntimeException;

class RefundExceedsPaymentException extends RuntimeException
{
    public function __construct(int $requested, int $refundable)
    {
        parent::__construct(
            "Refund amount {$requested} exceeds refundable amount {$refundable}."
        );
    }
}
