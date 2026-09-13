<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;

class InvalidDiscountException extends DomainException
{
    public function __construct(
        public string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        $status = in_array($this->errorCode, ['discount_expired', 'discount_usage_limit_reached'], true) ? 400 : 422;

        return response()->json([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ], $status);
    }
}
