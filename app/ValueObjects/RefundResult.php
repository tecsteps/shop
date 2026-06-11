<?php

namespace App\ValueObjects;

final readonly class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw  Mock provider response payload
     */
    public function __construct(
        public bool $success,
        public ?string $providerRefundId = null,
        public ?string $errorCode = null,
        public array $raw = [],
    ) {}

    public static function processed(string $providerRefundId, array $raw = []): self
    {
        return new self(true, $providerRefundId, null, $raw);
    }
}
