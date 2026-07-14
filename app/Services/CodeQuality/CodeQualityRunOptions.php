<?php

namespace App\Services\CodeQuality;

class CodeQualityRunOptions
{
    /**
     * @param  list<string>  $paths
     * @param  list<string>  $checks
     * @param  list<string>  $excludes
     */
    public function __construct(
        public readonly array $paths,
        public readonly array $checks,
        public readonly array $excludes,
        public readonly bool $changed,
        public readonly bool $staged,
        public readonly ?string $base,
        public readonly string $format,
        public readonly ?string $output,
        public readonly string $failOn,
        public readonly bool $ai,
        public readonly bool $noAi,
        public readonly ?string $aiModel,
        public readonly ?int $aiConcurrency,
        public readonly int $timeoutSeconds,
        public readonly int $checkTimeoutSeconds,
        public readonly string $profile,
    ) {}

    public function aiEnabled(): bool
    {
        return ! $this->noAi && ($this->ai || (bool) config('code_quality.ai.enabled', false));
    }

    public function hasExplicitAllChecks(): bool
    {
        return in_array('all', $this->checks, true);
    }
}
