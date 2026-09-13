<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;

class InsufficientInventoryException extends DomainException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'quantity' => [$this->getMessage()],
            ],
        ], 422);
    }
}
