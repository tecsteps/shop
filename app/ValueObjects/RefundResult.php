<?php

namespace App\ValueObjects;

final readonly class RefundResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(public bool $success, public ?string $providerRefundId = null, public ?string $errorCode = null, public array $raw = []) {}
}
