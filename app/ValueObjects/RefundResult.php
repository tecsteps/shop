<?php

namespace App\ValueObjects;

use App\Enums\RefundStatus;

/**
 * The result of a mock refund.
 */
final readonly class RefundResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $success,
        public string $providerRefundId,
        public RefundStatus $status,
        public array $raw = [],
    ) {}
}
