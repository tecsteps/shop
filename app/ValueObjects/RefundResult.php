<?php

namespace App\ValueObjects;

readonly class RefundResult
{
    /**
     * @param  array<string, mixed>|null  $rawResponse
     */
    public function __construct(
        public bool $success,
        public string $status,
        public string $providerRefundId,
        public ?array $rawResponse = null,
    ) {}
}
