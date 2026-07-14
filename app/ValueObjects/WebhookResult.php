<?php

namespace App\ValueObjects;

final readonly class WebhookResult
{
    public function __construct(public bool $success, public string $message) {}
}
