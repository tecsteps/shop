<?php

namespace App\Services\CodeQuality\Checks;

use App\Services\CodeQuality\AiReviewRequest;
use App\Services\CodeQuality\CheckContext;
use App\Services\CodeQuality\CheckResult;
use App\Services\CodeQuality\CodeQualityScope;
use App\Services\CodeQuality\Contracts\AiReviewer;
use App\Services\CodeQuality\Contracts\CodeQualityCheck;

class AiCodeQualityCheck implements CodeQualityCheck
{
    /**
     * @param  list<string>  $groups
     * @param  list<string>  $areas
     */
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly array $groups,
        private readonly array $areas,
        private readonly string $checklistExcerpt,
        private readonly AiReviewer $reviewer,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function groups(): array
    {
        return $this->groups;
    }

    public function supports(CodeQualityScope $scope): bool
    {
        return $this->matchingFiles($scope) !== [];
    }

    public function run(CodeQualityScope $scope, CheckContext $context): CheckResult
    {
        $started = hrtime(true);
        $files = $this->matchingFiles($scope);

        if ($files === []) {
            return CheckResult::skipped($this->id, 'No scoped files match this AI review.');
        }

        if (! $context->aiEnabled) {
            return CheckResult::skipped($this->id, 'AI checks are disabled. Pass --ai to run this review.');
        }

        $result = $this->reviewer->review(new AiReviewRequest(
            runId: $context->runId,
            checkId: $this->id,
            checkName: $this->name,
            basePath: $scope->basePath,
            files: $files,
            checklistExcerpt: $this->checklistExcerpt,
            deterministicFindings: $context->deterministicFindings,
            model: $context->aiModel ?? (string) config('code_quality.ai.model'),
            timeoutSeconds: $context->checkTimeoutSeconds,
        ));

        return CheckResult::completed(
            $this->id,
            (int) ((hrtime(true) - $started) / 1_000_000),
            $result->findings,
        );
    }

    /**
     * @return list<string>
     */
    private function matchingFiles(CodeQualityScope $scope): array
    {
        if ($this->areas === []) {
            return $scope->files;
        }

        return $scope->filesForAreas($this->areas);
    }
}
