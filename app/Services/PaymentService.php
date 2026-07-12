<?php

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\ValueObjects\PaymentResult;

final class PaymentService
{
    public function __construct(private readonly PaymentProvider $provider) {}

    /** @param array<string, mixed> $details */
    public function charge(Checkout $checkout, PaymentMethod $method, array $details = []): PaymentResult
    {
        return $this->provider->charge($checkout, $method, $details);
    }
}
