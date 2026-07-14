<?php

namespace App\Services\CodeQuality\Runners;

class ProcessResult
{
    /**
     * @param  list<string>  $command
     */
    public function __construct(
        public readonly array $command,
        public readonly int $exitCode,
        public readonly string $output,
        public readonly string $errorOutput,
        public readonly int $durationMs,
    ) {}

    public function successful(): bool
    {
        return $this->exitCode === 0;
    }
}
