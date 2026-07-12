<?php

namespace App\Services\CodeQuality;

use RuntimeException;

class CodeQualityException extends RuntimeException
{
    public function __construct(string $message, private readonly int $exitCode = 3)
    {
        parent::__construct($message);
    }

    public static function invalidInput(string $message): self
    {
        return new self($message, 2);
    }

    public static function checkerFailed(string $message): self
    {
        return new self($message, 3);
    }

    public static function timedOut(string $message): self
    {
        return new self($message, 4);
    }

    public static function aiUnavailable(string $message): self
    {
        return new self($message, 5);
    }

    public function exitCode(): int
    {
        return $this->exitCode;
    }
}
