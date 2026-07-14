<?php

namespace App\Services\CodeQuality;

use App\Services\CodeQuality\Findings\Finding;

class CheckContext
{
    /**
     * @param  list<string>  $selectedChecks
     * @param  list<Finding>  $deterministicFindings
     */
    public function __construct(
        public readonly string $runId,
        public readonly array $selectedChecks,
        public readonly bool $aiEnabled,
        public readonly ?string $aiModel,
        public readonly int $checkTimeoutSeconds,
        public readonly array $deterministicFindings = [],
    ) {}

    /**
     * @param  list<Finding>  $deterministicFindings
     */
    public function withDeterministicFindings(array $deterministicFindings): self
    {
        return new self(
            runId: $this->runId,
            selectedChecks: $this->selectedChecks,
            aiEnabled: $this->aiEnabled,
            aiModel: $this->aiModel,
            checkTimeoutSeconds: $this->checkTimeoutSeconds,
            deterministicFindings: $deterministicFindings,
        );
    }
}
