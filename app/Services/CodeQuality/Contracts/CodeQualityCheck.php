<?php

namespace App\Services\CodeQuality\Contracts;

use App\Services\CodeQuality\CheckContext;
use App\Services\CodeQuality\CheckResult;
use App\Services\CodeQuality\CodeQualityScope;

interface CodeQualityCheck
{
    public function id(): string;

    /**
     * @return list<string>
     */
    public function groups(): array;

    public function supports(CodeQualityScope $scope): bool;

    public function run(CodeQualityScope $scope, CheckContext $context): CheckResult;
}
