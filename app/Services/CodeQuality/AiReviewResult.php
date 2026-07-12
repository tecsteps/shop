<?php

namespace App\Services\CodeQuality;

use App\Services\CodeQuality\Findings\Finding;

class AiReviewResult
{
    /**
     * @param  list<Finding>  $findings
     */
    public function __construct(
        public readonly array $findings,
        public readonly ?string $rawOutput = null,
    ) {}
}
