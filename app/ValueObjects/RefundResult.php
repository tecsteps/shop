<?php

namespace App\ValueObjects;

final class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerRefundId,
        public readonly ?string $errorCode = null,
        public readonly array $raw = [],
    ) {}

    public static function succeeded(string $providerId, array $raw = []): self
    {
        return new self(true, $providerId, null, $raw);
    }

    public static function failed(string $errorCode, array $raw = []): self
    {
        return new self(false, null, $errorCode, $raw);
    }
}
