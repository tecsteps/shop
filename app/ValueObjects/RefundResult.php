<?php

namespace App\ValueObjects;

readonly class RefundResult
{
    public function __construct(public bool $successful, public string $reference, public string $message = '') {}
}
