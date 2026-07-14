<?php

namespace App\Services\CodeQuality\Findings;

use App\Services\CodeQuality\CheckResult;
use App\Services\CodeQuality\CodeQualityScope;

class Report
{
    /**
     * @param  array<string, mixed>  $configuration
     * @param  list<CheckResult>  $checkResults
     * @param  list<Finding>  $findings
     */
    public function __construct(
        public readonly string $runId,
        public readonly string $startedAt,
        public readonly string $finishedAt,
        public readonly string $status,
        public readonly string $profile,
        public readonly CodeQualityScope $scope,
        public readonly array $configuration,
        public readonly array $checkResults,
        public readonly array $findings,
    ) {}

    public function exitCode(): int
    {
        return match ($this->status) {
            'passed' => 0,
            'failed' => 1,
            'timed_out' => 4,
            default => 3,
        };
    }

    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        $summary = [
            'critical' => 0,
            'error' => 0,
            'warning' => 0,
            'info' => 0,
            'skipped_checks' => 0,
            'failed_checks' => 0,
        ];

        foreach ($this->findings as $finding) {
            if (array_key_exists($finding->severity, $summary)) {
                $summary[$finding->severity]++;
            }
        }

        foreach ($this->checkResults as $result) {
            if ($result->status === 'skipped') {
                $summary['skipped_checks']++;
            }

            if (in_array($result->status, ['errored', 'timed_out'], true)) {
                $summary['failed_checks']++;
            }
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => 1,
            'run_id' => $this->runId,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'status' => $this->status,
            'profile' => $this->profile,
            'scope' => $this->scope->toArray(),
            'configuration' => $this->configuration,
            'summary' => $this->summary(),
            'check_results' => array_map(
                fn (CheckResult $result): array => $result->toArray(),
                $this->checkResults,
            ),
            'findings' => array_map(
                fn (Finding $finding): array => $finding->toArray(),
                $this->findings,
            ),
        ];
    }
}
