<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;

class FulfillmentGuardException extends DomainException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 409);
    }
}
