<?php

namespace App\ValueObjects;

use App\Enums\PaymentStatus;

/**
 * The result of a mock payment charge.
 *
 * `status` is one of pending/captured/failed. On failure `errorCode` carries a
 * stable machine code such as `card_declined` or `insufficient_funds`. `raw` is
 * the full mock provider response, persisted (encrypted) on the payment record.
 */
final readonly class PaymentResult
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public bool $success,
        public string $referenceId,
        public PaymentStatus $status,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $raw = [],
    ) {}
}
