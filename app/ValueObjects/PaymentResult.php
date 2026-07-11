<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

final readonly class PaymentResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(public bool $success, public PaymentStatus $status, public ?string $providerPaymentId = null, public ?string $errorCode = null, public array $raw = []) {}
}
