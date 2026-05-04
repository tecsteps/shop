<?php

namespace App\ValueObjects;

use App\Enums\RefundStatus;

final readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public RefundStatus $status,
        public ?string $referenceId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    /**
     * @return array{success: bool, status: string, reference_id: string|null, error_code: string|null, error_message: string|null}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status->value,
            'reference_id' => $this->referenceId,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
        ];
    }
}
