<?php

namespace App\Exceptions;

use App\ValueObjects\PaymentResult;
use RuntimeException;

class PaymentFailedException extends RuntimeException
{
    public function __construct(
        public readonly string $paymentErrorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function fromResult(PaymentResult $result): self
    {
        return new self(
            $result->errorCode ?? 'payment_failed',
            $result->errorMessage ?? 'Payment could not be processed.',
        );
    }
}
