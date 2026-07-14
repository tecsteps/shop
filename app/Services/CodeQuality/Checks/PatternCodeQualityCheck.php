<?php

namespace App\Services\CodeQuality\Checks;

use App\Services\CodeQuality\CheckContext;
use App\Services\CodeQuality\CheckResult;
use App\Services\CodeQuality\CodeQualityScope;
use App\Services\CodeQuality\Contracts\CodeQualityCheck;
use App\Services\CodeQuality\Findings\Finding;
use Illuminate\Support\Str;

class PatternCodeQualityCheck implements CodeQualityCheck
{
    /**
     * @param  list<string>  $groups
     * @param  list<string>  $areas
     * @param  list<array<string, mixed>>  $patterns
     * @param  null|callable(CodeQualityScope, list<string>, CheckContext, self): list<Finding>  $scanner
     */
    public function __construct(
        private readonly string $id,
        private readonly array $groups,
        private readonly array $areas,
        private readonly array $patterns = [],
        private readonly mixed $scanner = null,
        private readonly ?string $skipMessage = null,
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
            return CheckResult::skipped($this->id, $this->skipMessage ?? 'No scoped files match this check.');
        }

        $findings = is_callable($this->scanner)
            ? ($this->scanner)($scope, $files, $context, $this)
            : $this->scanPatterns($files);

        return CheckResult::completed(
            $this->id,
            (int) ((hrtime(true) - $started) / 1_000_000),
            $findings,
        );
    }

    public function makeFinding(
        string $severity,
        string $confidence,
        string $category,
        string $title,
        string $description,
        ?string $file = null,
        ?int $line = null,
        ?string $evidence = null,
        ?string $recommendation = null,
        ?string $dedupeKey = null,
    ): Finding {
        return new Finding(
            id: 'cqf_'.Str::ulid(),
            checkId: $this->id,
            source: 'deterministic',
            severity: $severity,
            confidence: $confidence,
            category: $category,
            title: $title,
            description: $description,
            file: $file,
            line: $line,
            evidence: $evidence,
            recommendation: $recommendation,
            docs: [],
            dedupeKey: $dedupeKey,
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

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanPatterns(array $files): array
    {
        $findings = [];

        foreach ($files as $file) {
            $absolutePath = base_path($file);

            if (! is_readable($absolutePath)) {
                continue;
            }

            $lines = file($absolutePath, FILE_IGNORE_NEW_LINES);

            if ($lines === false) {
                continue;
            }

            foreach ($lines as $index => $line) {
                foreach ($this->patterns as $pattern) {
                    if (! preg_match((string) $pattern['regex'], $line, $matches)) {
                        continue;
                    }

                    $evidence = (bool) ($pattern['redact_evidence'] ?? false)
                        ? (string) ($pattern['evidence'] ?? 'Line matches a sensitive-risk pattern.')
                        : trim($line);

                    $findings[] = $this->makeFinding(
                        severity: (string) $pattern['severity'],
                        confidence: (string) ($pattern['confidence'] ?? 'high'),
                        category: (string) $pattern['category'],
                        title: (string) $pattern['title'],
                        description: (string) $pattern['description'],
                        file: $file,
                        line: $index + 1,
                        evidence: $evidence,
                        recommendation: (string) ($pattern['recommendation'] ?? ''),
                        dedupeKey: $this->id.':'.$file.':'.($index + 1).':'.Str::slug((string) $pattern['title']),
                    );
                }
            }
        }

        return $findings;
    }
}
