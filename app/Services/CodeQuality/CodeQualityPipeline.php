<?php

namespace App\Services\CodeQuality;

use App\Services\CodeQuality\Findings\Finding;
use App\Services\CodeQuality\Findings\Report;
use App\Services\CodeQuality\Support\Severity;
use Illuminate\Support\Str;
use Throwable;

class CodeQualityPipeline
{
    public function __construct(
        private readonly ScopeResolver $scopes,
        private readonly CheckRegistry $registry,
    ) {}

    public function run(CodeQualityRunOptions $options): Report
    {
        $startedAt = now('UTC');
        $runId = 'cqr_'.Str::ulid();
        $scope = $this->scopes->resolve($options);
        $selectedChecks = $this->registry->expand($options->checks, $options->aiEnabled(), $options->noAi);
        $context = new CheckContext(
            runId: $runId,
            selectedChecks: $selectedChecks,
            aiEnabled: $options->aiEnabled(),
            aiModel: $options->aiModel ?? (string) config('code_quality.ai.model'),
            checkTimeoutSeconds: $options->checkTimeoutSeconds,
        );

        $results = [];
        $findings = [];
        $deterministicFindings = [];

        foreach ($this->registry->checksFor($selectedChecks) as $check) {
            if (! $check->supports($scope)) {
                $results[] = CheckResult::skipped($check->id(), 'No scoped files match this check.');

                continue;
            }

            try {
                $result = $check->run($scope, $context);
            } catch (CodeQualityException $exception) {
                if ($exception->exitCode() === 4) {
                    $results[] = CheckResult::timedOut($check->id(), $exception->getMessage());

                    continue;
                }

                if ($exception->exitCode() === 5) {
                    throw $exception;
                }

                $results[] = CheckResult::errored($check->id(), $exception->getMessage());

                continue;
            } catch (Throwable $exception) {
                report($exception);
                $results[] = CheckResult::errored($check->id(), $exception->getMessage());

                continue;
            }

            $results[] = $result;
            $findings = [...$findings, ...$result->findings];

            foreach ($result->findings as $finding) {
                if ($finding->source === 'deterministic') {
                    $deterministicFindings[] = $finding;
                }
            }

            $context = $context->withDeterministicFindings($deterministicFindings);
        }

        $findings = $this->markBlocking(
            findings: $this->dedupeFindings($findings),
            failOn: $options->failOn,
        );

        return new Report(
            runId: $runId,
            startedAt: $startedAt->toJSON(),
            finishedAt: now('UTC')->toJSON(),
            status: $this->status($results, $findings),
            profile: $options->profile,
            scope: $scope,
            configuration: [
                'checks' => $selectedChecks,
                'ai_enabled' => $options->aiEnabled(),
                'ai_model' => $options->aiEnabled() ? ($options->aiModel ?? config('code_quality.ai.model')) : null,
                'ai_concurrency' => $options->aiConcurrency ?? config('code_quality.ai.concurrency'),
                'fail_on' => $options->failOn,
            ],
            checkResults: $results,
            findings: $findings,
        );
    }

    /**
     * @param  list<Finding>  $findings
     * @return list<Finding>
     */
    private function dedupeFindings(array $findings): array
    {
        $deduped = [];

        foreach ($findings as $finding) {
            $key = $finding->dedupeIdentity();

            if (! array_key_exists($key, $deduped)) {
                $deduped[$key] = $finding;

                continue;
            }

            $existing = $deduped[$key];

            if ($existing->source === 'ai' && $finding->source === 'deterministic') {
                $deduped[$key] = $finding->withRelatedNote($existing->description);

                continue;
            }

            if ($existing->source === 'deterministic' && $finding->source === 'ai') {
                $deduped[$key] = $existing->withRelatedNote($finding->description);
            }
        }

        return array_values($deduped);
    }

    /**
     * @param  list<Finding>  $findings
     * @return list<Finding>
     */
    private function markBlocking(array $findings, string $failOn): array
    {
        return array_map(
            fn (Finding $finding): Finding => $finding->withBlocking(Severity::blocks(
                source: $finding->source,
                severity: $finding->severity,
                confidence: $finding->confidence,
                threshold: $failOn,
            )),
            $findings,
        );
    }

    /**
     * @param  list<CheckResult>  $results
     * @param  list<Finding>  $findings
     */
    private function status(array $results, array $findings): string
    {
        if (collect($results)->contains(fn (CheckResult $result): bool => $result->status === 'timed_out')) {
            return 'timed_out';
        }

        if (collect($results)->contains(fn (CheckResult $result): bool => $result->status === 'errored')) {
            return 'errored';
        }

        if (collect($findings)->contains(fn (Finding $finding): bool => $finding->blocking)) {
            return 'failed';
        }

        return 'passed';
    }
}
