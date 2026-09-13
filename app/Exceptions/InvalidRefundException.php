<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;

class InvalidRefundException extends DomainException
{
    public function __construct(string $message, public int $status = 422)
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->status);
    }
}
