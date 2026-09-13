<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;

class UnserviceableAddressException extends DomainException
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => [
                'shipping_address.country_code' => [$this->getMessage()],
            ],
        ], 422);
    }
}
