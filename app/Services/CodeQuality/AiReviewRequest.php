<?php

namespace App\Services\CodeQuality;

use App\Services\CodeQuality\Findings\Finding;

class AiReviewRequest
{
    /**
     * @param  list<string>  $files
     * @param  list<Finding>  $deterministicFindings
     */
    public function __construct(
        public readonly string $runId,
        public readonly string $checkId,
        public readonly string $checkName,
        public readonly string $basePath,
        public readonly array $files,
        public readonly string $checklistExcerpt,
        public readonly array $deterministicFindings,
        public readonly string $model,
        public readonly int $timeoutSeconds,
    ) {}
}
