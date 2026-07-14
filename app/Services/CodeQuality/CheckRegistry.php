<?php

namespace App\Services\CodeQuality;

use App\Services\CodeQuality\Checks\AiCodeQualityCheck;
use App\Services\CodeQuality\Checks\PatternCodeQualityCheck;
use App\Services\CodeQuality\Contracts\AiReviewer;
use App\Services\CodeQuality\Contracts\CodeQualityCheck;
use App\Services\CodeQuality\Findings\Finding;
use Illuminate\Support\Str;

class CheckRegistry
{
    private const DETERMINISTIC_IDS = [
        'deterministic.tenancy',
        'deterministic.secrets',
        'deterministic.llm-boundaries',
        'deterministic.ai-free-boundaries',
        'deterministic.request-validation',
        'deterministic.authorization',
        'deterministic.config-env',
        'deterministic.http-client',
        'deterministic.queue-stability',
        'deterministic.migrations',
        'deterministic.frontend-routes',
        'deterministic.dependencies',
    ];

    private const AI_IDS = [
        'ai.architecture',
        'ai.clean-code',
        'ai.security-review',
        'ai.stability',
        'ai.test-quality',
        'ai.frontend-ux',
    ];

    public function __construct(private readonly AiReviewer $aiReviewer) {}

    /**
     * @return list<string>
     */
    public function expand(array $requestedChecks, bool $aiEnabled, bool $noAi): array
    {
        $requestedChecks = $this->normalizeRequestedChecks($requestedChecks);
        $requestedChecks = $requestedChecks === [] ? (array) config('code_quality.defaults.checks', ['deterministic']) : $requestedChecks;
        $expanded = [];

        foreach ($requestedChecks as $requestedCheck) {
            if ($requestedCheck === 'all') {
                $expanded = [
                    ...$expanded,
                    ...self::DETERMINISTIC_IDS,
                    ...($aiEnabled ? self::AI_IDS : []),
                ];

                continue;
            }

            if (array_key_exists($requestedCheck, $this->groups())) {
                $expanded = [...$expanded, ...$this->groups()[$requestedCheck]];

                continue;
            }

            if (! in_array($requestedCheck, [...self::DETERMINISTIC_IDS, ...self::AI_IDS], true)) {
                throw CodeQualityException::invalidInput("Unknown code-quality check or group: {$requestedCheck}");
            }

            $expanded[] = $requestedCheck;
        }

        if ($noAi) {
            $expanded = array_values(array_filter(
                $expanded,
                fn (string $checkId): bool => ! Str::startsWith($checkId, 'ai.'),
            ));
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @param  list<string>  $ids
     * @return list<CodeQualityCheck>
     */
    public function checksFor(array $ids): array
    {
        $checks = $this->all();

        return array_values(array_map(
            fn (string $id): CodeQualityCheck => $checks[$id],
            $ids,
        ));
    }

    /**
     * @return array<string, CodeQualityCheck>
     */
    public function all(): array
    {
        return [
            'deterministic.tenancy' => new PatternCodeQualityCheck(
                id: 'deterministic.tenancy',
                groups: ['deterministic', 'security', 'laravel'],
                areas: ['php_backend'],
                scanner: fn (CodeQualityScope $scope, array $files, CheckContext $context, PatternCodeQualityCheck $check): array => $this->scanTenantModels($files, $check),
            ),
            'deterministic.secrets' => new PatternCodeQualityCheck(
                id: 'deterministic.secrets',
                groups: ['deterministic', 'security', 'laravel', 'frontend', 'worker'],
                areas: ['php_backend', 'php_tests', 'frontend', 'worker'],
                patterns: [[
                    'regex' => '/(?:Log::(?:debug|info|notice|warning|error|critical|alert)|Inertia::render|console\.(?:log|debug|error))\([^;\n]*(password|token|secret|api_?key|credential|authorization|cookie)/i',
                    'severity' => 'error',
                    'confidence' => 'medium',
                    'category' => 'security',
                    'title' => 'Secret-like value is passed to an unsafe output sink',
                    'description' => 'Risky sinks must not receive credential-like values unless they are redacted first.',
                    'recommendation' => 'Pass only redacted fields or remove the secret-like value from the output payload.',
                    'redact_evidence' => true,
                    'evidence' => 'A secret-like identifier is passed to a logging, rendering, or console sink.',
                ]],
            ),
            'deterministic.llm-boundaries' => new PatternCodeQualityCheck(
                id: 'deterministic.llm-boundaries',
                groups: ['deterministic', 'stability', 'worker'],
                areas: ['php_backend', 'worker', 'frontend'],
                scanner: fn (CodeQualityScope $scope, array $files, CheckContext $context, PatternCodeQualityCheck $check): array => $this->scanHardcodedModelStrings($files, $check),
            ),
            'deterministic.ai-free-boundaries' => new PatternCodeQualityCheck(
                id: 'deterministic.ai-free-boundaries',
                groups: ['deterministic', 'stability', 'security'],
                areas: ['php_backend', 'php_tests', 'worker', 'frontend'],
                scanner: fn (CodeQualityScope $scope, array $files, CheckContext $context, PatternCodeQualityCheck $check): array => $this->scanAiFreeBoundaryReferences($files, $check),
            ),
            'deterministic.request-validation' => new PatternCodeQualityCheck(
                id: 'deterministic.request-validation',
                groups: ['deterministic', 'security', 'laravel'],
                areas: ['php_backend'],
                patterns: [[
                    'regex' => '/\$request\s*->\s*all\s*\(/',
                    'severity' => 'error',
                    'confidence' => 'high',
                    'category' => 'validation',
                    'title' => 'Request payload is mass-read with all()',
                    'description' => 'State-changing code should consume validated input, not the full request payload.',
                    'recommendation' => 'Use a Form Request and $request->validated(), or explicitly validate before reading input.',
                ]],
            ),
            'deterministic.authorization' => new PatternCodeQualityCheck(
                id: 'deterministic.authorization',
                groups: ['deterministic', 'security', 'laravel'],
                areas: ['php_backend'],
                scanner: fn (CodeQualityScope $scope, array $files, CheckContext $context, PatternCodeQualityCheck $check): array => $this->scanControllerAuthorization($files, $check),
            ),
            'deterministic.config-env' => new PatternCodeQualityCheck(
                id: 'deterministic.config-env',
                groups: ['deterministic', 'laravel', 'stability'],
                areas: ['php_backend', 'php_tests'],
                scanner: fn (CodeQualityScope $scope, array $files, CheckContext $context, PatternCodeQualityCheck $check): array => $this->scanEnvOutsideConfig($files, $check),
            ),
            'deterministic.http-client' => new PatternCodeQualityCheck(
                id: 'deterministic.http-client',
                groups: ['deterministic', 'stability', 'laravel'],
                areas: ['php_backend'],
                scanner: fn (CodeQualityScope $scope, array $files, CheckContext $context, PatternCodeQualityCheck $check): array => $this->scanHttpTimeouts($files, $check),
            ),
            'deterministic.queue-stability' => new PatternCodeQualityCheck(
                id: 'deterministic.queue-stability',
                groups: ['deterministic', 'stability', 'laravel'],
                areas: ['php_backend'],
                scanner: fn (CodeQualityScope $scope, array $files, CheckContext $context, PatternCodeQualityCheck $check): array => $this->scanQueueJobs($files, $check),
            ),
            'deterministic.migrations' => new PatternCodeQualityCheck(
                id: 'deterministic.migrations',
                groups: ['deterministic', 'laravel', 'stability'],
                areas: ['php_backend'],
                scanner: fn (CodeQualityScope $scope, array $files, CheckContext $context, PatternCodeQualityCheck $check): array => $this->scanMigrations($files, $check),
            ),
            'deterministic.frontend-routes' => new PatternCodeQualityCheck(
                id: 'deterministic.frontend-routes',
                groups: ['deterministic', 'frontend'],
                areas: ['frontend', 'php_tests'],
                patterns: [[
                    'regex' => '/(?:router\.(?:get|post|put|patch|delete|visit)|fetch)\(\s*[\'"]\/(?:app|api|internal)\b|href=[\'"]\/app\//',
                    'severity' => 'warning',
                    'confidence' => 'medium',
                    'category' => 'frontend-routing',
                    'title' => 'Frontend route appears hardcoded',
                    'description' => 'Frontend calls into Laravel should prefer typed or named route helpers when the application provides them.',
                    'recommendation' => 'Use the project route helper, generated action helper, or named route helper instead of a hardcoded application URL.',
                ]],
            ),
            'deterministic.dependencies' => new PatternCodeQualityCheck(
                id: 'deterministic.dependencies',
                groups: ['deterministic', 'security', 'stability'],
                areas: ['config'],
                scanner: fn (CodeQualityScope $scope, array $files, CheckContext $context, PatternCodeQualityCheck $check): array => $this->scanDependencyFiles($files, $check),
            ),
            'ai.architecture' => $this->aiCheck('ai.architecture', 'architecture review', ['ai', 'laravel', 'worker'], ['php_backend', 'frontend', 'worker', 'check_spec'], 'Architecture boundaries, misplaced domain decisions, and unjustified abstractions.'),
            'ai.clean-code' => $this->aiCheck('ai.clean-code', 'clean code review', ['ai', 'laravel', 'frontend', 'worker'], ['php_backend', 'frontend', 'worker', 'php_tests'], 'Names, abstraction levels, duplication, and reviewability.'),
            'ai.security-review' => $this->aiCheck('ai.security-review', 'security review', ['ai', 'security'], ['php_backend', 'frontend', 'worker'], 'Access control, secret exposure, SSRF, and trust-boundary issues.'),
            'ai.stability' => $this->aiCheck('ai.stability', 'stability review', ['ai', 'stability'], ['php_backend', 'worker'], 'Idempotency, retries, timeouts, partial failure, overload, and alert noise.'),
            'ai.test-quality' => $this->aiCheck('ai.test-quality', 'test quality review', ['ai', 'tests'], ['php_tests', 'worker'], 'Negative paths, meaningful assertions, hidden external systems, and eval coverage.'),
            'ai.frontend-ux' => $this->aiCheck('ai.frontend-ux', 'frontend UX review', ['ai', 'frontend'], ['frontend', 'blade'], 'Loading, empty, error, disabled states, accessibility, routing helpers, and application copy consistency.'),
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function groups(): array
    {
        return [
            'deterministic' => self::DETERMINISTIC_IDS,
            'ai' => self::AI_IDS,
            'security' => [
                'deterministic.tenancy',
                'deterministic.secrets',
                'deterministic.authorization',
                'deterministic.dependencies',
                'ai.security-review',
            ],
            'stability' => [
                'deterministic.llm-boundaries',
                'deterministic.http-client',
                'deterministic.queue-stability',
                'deterministic.migrations',
                'ai.stability',
            ],
            'laravel' => [
                'deterministic.tenancy',
                'deterministic.request-validation',
                'deterministic.authorization',
                'deterministic.config-env',
                'deterministic.http-client',
                'deterministic.queue-stability',
                'deterministic.migrations',
                'ai.architecture',
                'ai.clean-code',
            ],
            'frontend' => [
                'deterministic.frontend-routes',
                'deterministic.secrets',
                'ai.frontend-ux',
            ],
            'worker' => [
                'deterministic.llm-boundaries',
                'deterministic.ai-free-boundaries',
                'ai.stability',
            ],
            'tests' => [
                'ai.test-quality',
            ],
        ];
    }

    /**
     * @param  list<string>  $requestedChecks
     * @return list<string>
     */
    private function normalizeRequestedChecks(array $requestedChecks): array
    {
        return array_values(array_filter(array_map(
            'trim',
            collect($requestedChecks)
                ->flatMap(fn (string $check): array => explode(',', $check))
                ->all(),
        )));
    }

    /**
     * @param  list<string>  $groups
     * @param  list<string>  $areas
     */
    private function aiCheck(string $id, string $name, array $groups, array $areas, string $checklistExcerpt): AiCodeQualityCheck
    {
        return new AiCodeQualityCheck($id, $name, $groups, $areas, $checklistExcerpt, $this->aiReviewer);
    }

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanEnvOutsideConfig(array $files, PatternCodeQualityCheck $check): array
    {
        $findings = [];

        foreach ($files as $file) {
            if (Str::startsWith($file, 'config/')) {
                continue;
            }

            $content = $this->readFile($file);

            if ($content === null) {
                continue;
            }

            $tokens = token_get_all($content);

            foreach ($tokens as $index => $token) {
                if (! is_array($token) || $token[0] !== T_STRING || mb_strtolower($token[1]) !== 'env') {
                    continue;
                }

                if ($this->nextMeaningfulTokenText($tokens, $index) !== '(') {
                    continue;
                }

                $line = (int) $token[2];
                $findings[] = $check->makeFinding(
                    severity: 'error',
                    confidence: 'high',
                    category: 'configuration',
                    title: 'env() is used outside configuration',
                    description: 'Direct env() reads can return null after config caching.',
                    file: $file,
                    line: $line,
                    evidence: 'env() call',
                    recommendation: 'Move the env() read into a config file and use config() from application code.',
                    dedupeKey: $check->id().':'.$file.':'.$line.':env-outside-config',
                );
            }
        }

        return $findings;
    }

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanHardcodedModelStrings(array $files, PatternCodeQualityCheck $check): array
    {
        $findings = [];

        foreach ($files as $file) {
            if (Str::startsWith($file, ['config/', 'resources/prompts/', 'specs/', 'docs/'])) {
                continue;
            }

            $content = $this->readFile($file);

            if ($content === null) {
                continue;
            }

            foreach (token_get_all($content) as $token) {
                if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    continue;
                }

                $value = stripcslashes(trim($token[1], '\'"'));

                if (preg_match('/^(?:gpt-|claude-|gemini-|anthropic\/|google\/|openai\/)[\w.\/-]+$/i', $value) !== 1) {
                    continue;
                }

                $line = (int) $token[2];
                $findings[] = $check->makeFinding(
                    severity: 'error',
                    confidence: 'high',
                    category: 'llm-boundary',
                    title: 'Model name is hardcoded outside configuration',
                    description: 'LLM model selection should stay in configuration or prompt metadata so operations can change models without code edits.',
                    file: $file,
                    line: $line,
                    evidence: 'Model-like string literal: '.$value,
                    recommendation: 'Move the model value into configuration and read it through config().',
                    dedupeKey: $check->id().':'.$file.':'.$line.':hardcoded-model',
                );
            }
        }

        return $findings;
    }

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanHttpTimeouts(array $files, PatternCodeQualityCheck $check): array
    {
        $findings = [];

        foreach ($files as $file) {
            $content = $this->readFile($file);

            if ($content === null || ! str_contains($content, 'Http::')) {
                continue;
            }

            $tokens = token_get_all($content);

            foreach ($tokens as $index => $token) {
                if (! is_array($token) || $token[0] !== T_STRING || $token[1] !== 'Http') {
                    continue;
                }

                if ($this->nextMeaningfulTokenText($tokens, $index) !== '::') {
                    continue;
                }

                $statement = $this->statementFromTokens($tokens, $index);

                if (str_contains($statement, 'fake(') || str_contains($statement, 'preventStrayRequests(')) {
                    continue;
                }

                if (str_contains($statement, 'timeout(') && str_contains($statement, 'connectTimeout(')) {
                    continue;
                }

                $line = (int) $token[2];
                $findings[] = $check->makeFinding(
                    severity: 'warning',
                    confidence: 'high',
                    category: 'stability',
                    title: 'HTTP client call is missing explicit timeouts',
                    description: 'External HTTP calls should set both connectTimeout and timeout so workers do not hang.',
                    file: $file,
                    line: $line,
                    evidence: Str::limit(trim(preg_replace('/\s+/', ' ', $statement) ?? ''), 220),
                    recommendation: 'Add connectTimeout(), timeout(), and explicit response error handling to the HTTP chain.',
                    dedupeKey: $check->id().':'.$file.':'.$line.':http-timeout',
                );
            }
        }

        return $findings;
    }

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanAiFreeBoundaryReferences(array $files, PatternCodeQualityCheck $check): array
    {
        $keywords = array_values(array_filter(array_map(
            fn (string $keyword): string => Str::lower(trim($keyword)),
            (array) config('code_quality.domain.ai_free_path_keywords', []),
        )));

        if ($keywords === []) {
            return [];
        }

        $aiFreeFiles = array_values(array_filter(
            $files,
            fn (string $file): bool => Str::contains(Str::lower($file), $keywords),
        ));

        $findings = [];

        foreach ($aiFreeFiles as $file) {
            $findings = [...$findings, ...$this->lineFindings(
                $file,
                '/(?:use\s+.*(?:Llm|OpenRouter|OpenAI|Ai)|import\s+.*(?:llm|openai|openrouter|prompts)|from\s+[\'"].*(?:llm|openai|openrouter|prompts)|\b(?:LlmClient|OpenRouter|OpenAI)::)/i',
                fn (int $line, string $evidence): Finding => $check->makeFinding(
                    severity: 'critical',
                    confidence: 'high',
                    category: 'ai-boundary',
                    title: 'AI-free code boundary references AI infrastructure',
                    description: 'Configured AI-free paths must remain deterministic and must not import LLM or prompt infrastructure.',
                    file: $file,
                    line: $line,
                    evidence: $evidence,
                    recommendation: 'Move AI-dependent behavior out of the configured AI-free boundary or remove the path keyword from config if it is not intended.',
                    dedupeKey: $check->id().':'.$file.':'.$line.':ai-free-boundary',
                ),
            )];
        }

        return $findings;
    }

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanQueueJobs(array $files, PatternCodeQualityCheck $check): array
    {
        $findings = [];

        foreach ($files as $file) {
            if (! Str::startsWith($file, 'app/Jobs/')) {
                continue;
            }

            $content = $this->readFile($file);

            if ($content === null || ! str_contains($content, 'ShouldQueue')) {
                continue;
            }

            $propertyType = '(?:[?\\\\A-Za-z_][\\\\A-Za-z0-9_|?]*\\s+)?';
            $hasRetryPolicy = preg_match('/(?:public|protected)\s+'.$propertyType.'\$tries|function\s+backoff|(?:public|protected)\s+'.$propertyType.'\$backoff|function\s+retryUntil/', $content) === 1;
            $hasTimeout = preg_match('/(?:public|protected)\s+'.$propertyType.'\$timeout/', $content) === 1;

            if ($hasRetryPolicy && $hasTimeout) {
                continue;
            }

            $findings[] = $check->makeFinding(
                severity: 'warning',
                confidence: 'medium',
                category: 'queue',
                title: 'Queued job has incomplete retry or timeout policy',
                description: 'Jobs should state retry/backoff and timeout behavior when they may do slow or external work.',
                file: $file,
                line: 1,
                evidence: 'The job implements ShouldQueue without both retry/backoff and timeout declarations.',
                recommendation: 'Add timeout and retry/backoff behavior, or document why the job is intentionally trivial.',
                dedupeKey: $check->id().':'.$file.':queue-policy',
            );
        }

        return $findings;
    }

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanMigrations(array $files, PatternCodeQualityCheck $check): array
    {
        $findings = [];

        foreach (array_filter($files, fn (string $file): bool => Str::startsWith($file, 'database/migrations/')) as $file) {
            $content = $this->readFile($file);

            if ($content === null) {
                continue;
            }

            if (preg_match('/DB::(?:statement|select|unprepared|update|delete|insert)\s*\(/', $content, $match, PREG_OFFSET_CAPTURE) === 1) {
                $line = substr_count(substr($content, 0, (int) $match[0][1]), "\n") + 1;
                $findings[] = $check->makeFinding(
                    severity: 'warning',
                    confidence: 'high',
                    category: 'migration',
                    title: 'Migration uses raw SQL',
                    description: 'Raw SQL in migrations needs explicit justification and database portability review.',
                    file: $file,
                    line: $line,
                    evidence: trim($match[0][0]),
                    recommendation: 'Prefer Schema builder methods or add a clear portability justification.',
                    dedupeKey: $check->id().':'.$file.':'.$line.':raw-sql',
                );
            }

            if (! str_contains($content, 'function down(')) {
                $findings[] = $check->makeFinding(
                    severity: 'warning',
                    confidence: 'medium',
                    category: 'migration',
                    title: 'Migration has no down() method',
                    description: 'Migrations should be reversible unless intentionally forward-only.',
                    file: $file,
                    line: 1,
                    evidence: 'No down() method was found.',
                    recommendation: 'Add a down() method or document the migration as intentionally irreversible.',
                    dedupeKey: $check->id().':'.$file.':missing-down',
                );
            }
        }

        return $findings;
    }

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanTenantModels(array $files, PatternCodeQualityCheck $check): array
    {
        if (! (bool) config('code_quality.domain.multi_tenant', false)) {
            return [];
        }

        $allowlist = array_values(array_map('strval', [
            ...(array) config('code_quality.allowlists.global_models', []),
            ...(array) config('code_quality.allowlists.tenant_via_parent_models', []),
        ]));
        $tenantKeys = array_values(array_filter(array_map('strval', (array) config('code_quality.domain.tenant_keys', []))));
        $findings = [];

        if ($tenantKeys === []) {
            return [];
        }

        foreach (array_filter($files, fn (string $file): bool => Str::startsWith($file, 'app/Models/')) as $file) {
            $content = $this->readFile($file);

            if ($content === null || ! str_contains($content, 'extends Model')) {
                continue;
            }

            $model = pathinfo($file, PATHINFO_FILENAME);

            if (in_array($model, $allowlist, true) || Str::contains($content, $tenantKeys)) {
                continue;
            }

            $findings[] = $check->makeFinding(
                severity: 'warning',
                confidence: 'medium',
                category: 'tenancy',
                title: 'Model does not declare an obvious tenant boundary',
                description: 'Tenant-owned models should include a configured tenant key or be allowlisted as global.',
                file: $file,
                line: 1,
                evidence: "Model {$model} extends Model without a configured tenant key reference.",
                recommendation: 'Add tenant scoping for tenant-owned data or allowlist the model as global with justification.',
                dedupeKey: $check->id().':'.$file.':tenant-boundary',
            );
        }

        return $findings;
    }

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanControllerAuthorization(array $files, PatternCodeQualityCheck $check): array
    {
        $findings = [];
        $allowlist = array_values(array_map('strval', (array) config('code_quality.allowlists.authorization_methods', [])));

        foreach (array_filter($files, fn (string $file): bool => Str::contains($file, 'Controller.php')) as $file) {
            $content = $this->readFile($file);

            if ($content === null) {
                continue;
            }

            preg_match_all('/function\s+(index|show|store|update|destroy|delete)\s*\([^)]*\)\s*:[^{]+{(?P<body>.*?)(?=\n    (?:public|protected|private)\s+function|\n})/s', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

            foreach ($matches as $match) {
                $method = $match[1][0];
                $body = $match['body'][0];

                if (in_array($file.':'.$method, $allowlist, true)
                    || preg_match('/(?:authorize|Gate::|->can\(|middleware\([\'"]can:)/', $body) === 1) {
                    continue;
                }

                $line = substr_count(substr($content, 0, (int) $match[0][1]), "\n") + 1;
                $findings[] = $check->makeFinding(
                    severity: 'warning',
                    confidence: 'low',
                    category: 'authorization',
                    title: 'Controller method has no obvious authorization check',
                    description: 'Tenant data reads and state-changing methods should authorize through policies, gates, or route middleware.',
                    file: $file,
                    line: $line,
                    evidence: "Method {$method} has no obvious authorization call in its body.",
                    recommendation: 'Confirm route middleware or add an explicit policy/gate authorization check.',
                    dedupeKey: $check->id().':'.$file.':'.$method.':authorization',
                );
            }
        }

        return $findings;
    }

    /**
     * @param  list<string>  $files
     * @return list<Finding>
     */
    private function scanDependencyFiles(array $files, PatternCodeQualityCheck $check): array
    {
        $dependencyFiles = array_values(array_filter(
            $files,
            fn (string $file): bool => in_array($file, ['composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'pnpm-lock.yaml', 'yarn.lock'], true),
        ));

        if ($dependencyFiles === []) {
            return [];
        }

        return array_map(
            fn (string $file): Finding => $check->makeFinding(
                severity: 'warning',
                confidence: 'high',
                category: 'dependencies',
                title: 'Dependency manifest or lockfile is in scope',
                description: 'Dependency changes require audit/license checks before they should merge.',
                file: $file,
                line: 1,
                evidence: 'Dependency file is included in the code-quality scope.',
                recommendation: 'Run the configured package audit and license checks, then include the result in the handoff.',
                dedupeKey: $check->id().':'.$file.':dependency-review',
            ),
            $dependencyFiles,
        );
    }

    /**
     * @return list<Finding>
     */
    private function lineFindings(string $file, string $regex, callable $factory): array
    {
        $lines = file(base_path($file), FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return [];
        }

        $findings = [];

        foreach ($lines as $index => $line) {
            if (preg_match($regex, $line) === 1) {
                $findings[] = $factory($index + 1, trim($line));
            }
        }

        return $findings;
    }

    private function readFile(string $file): ?string
    {
        $content = @file_get_contents(base_path($file));

        return $content === false ? null : $content;
    }

    /**
     * @param  list<int|string|array{int, string, int}>  $tokens
     */
    private function nextMeaningfulTokenText(array $tokens, int $index): ?string
    {
        for ($cursor = $index + 1; $cursor < count($tokens); $cursor++) {
            $token = $tokens[$cursor];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $this->tokenText($token);
        }

        return null;
    }

    /**
     * @param  list<int|string|array{int, string, int}>  $tokens
     */
    private function statementFromTokens(array $tokens, int $index): string
    {
        $statement = '';

        for ($cursor = $index; $cursor < count($tokens); $cursor++) {
            $text = $this->tokenText($tokens[$cursor]);
            $statement .= $text;

            if ($text === ';') {
                break;
            }
        }

        return $statement;
    }

    /**
     * @param  int|string|array{int, string, int}  $token
     */
    private function tokenText(int|string|array $token): string
    {
        return is_array($token) ? $token[1] : (string) $token;
    }
}
