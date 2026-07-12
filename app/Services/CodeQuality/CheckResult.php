<?php

namespace App\Services\CodeQuality;

use App\Services\CodeQuality\Findings\Finding;

class CheckResult
{
    /**
     * @param  list<Finding>  $findings
     */
    public function __construct(
        public readonly string $checkId,
        public readonly string $status,
        public readonly int $durationMs,
        public readonly array $findings = [],
        public readonly ?string $message = null,
    ) {}

    /**
     * @param  list<Finding>  $findings
     */
    public static function completed(string $checkId, int $durationMs, array $findings): self
    {
        return new self(
            checkId: $checkId,
            status: $findings === [] ? 'passed' : 'failed',
            durationMs: $durationMs,
            findings: $findings,
        );
    }

    public static function skipped(string $checkId, string $message, int $durationMs = 0): self
    {
        return new self($checkId, 'skipped', $durationMs, [], $message);
    }

    public static function errored(string $checkId, string $message, int $durationMs = 0): self
    {
        return new self($checkId, 'errored', $durationMs, [], $message);
    }

    public static function timedOut(string $checkId, string $message, int $durationMs = 0): self
    {
        return new self($checkId, 'timed_out', $durationMs, [], $message);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'check_id' => $this->checkId,
            'status' => $this->status,
            'duration_ms' => $this->durationMs,
            'findings_count' => count($this->findings),
            'message' => $this->message,
        ], fn (mixed $value): bool => $value !== null);
    }
}
