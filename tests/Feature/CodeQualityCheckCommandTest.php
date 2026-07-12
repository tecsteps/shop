<?php

use App\Services\CodeQuality\AiReviewRequest;
use App\Services\CodeQuality\AiReviewResult;
use App\Services\CodeQuality\CodeQualityException;
use App\Services\CodeQuality\Contracts\AiReviewer;
use App\Services\CodeQuality\Findings\Finding;
use App\Services\CodeQuality\Runners\CodexAiRunner;
use App\Services\CodeQuality\Runners\ProcessResult;
use App\Services\CodeQuality\Runners\ProcessRunner;
use Illuminate\Support\Facades\Artisan;

function codeQualityJsonOutput(): array
{
    return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
}

function fakeAiReviewer(?CodeQualityException $exception = null): object
{
    return new class($exception) implements AiReviewer
    {
        /**
         * @var list<AiReviewRequest>
         */
        public array $requests = [];

        public function __construct(private readonly ?CodeQualityException $exception) {}

        public function review(AiReviewRequest $request): AiReviewResult
        {
            $this->requests[] = $request;

            if ($this->exception !== null) {
                throw $this->exception;
            }

            return new AiReviewResult([
                new Finding(
                    id: 'cqf_test_ai',
                    checkId: $request->checkId,
                    source: 'ai',
                    severity: 'error',
                    confidence: 'high',
                    category: 'architecture',
                    title: 'Fake AI finding',
                    description: 'The fake reviewer found a concrete issue.',
                    file: $request->files[0] ?? null,
                    line: 1,
                    evidence: 'Fake evidence.',
                    recommendation: 'Fix the fake issue.',
                    dedupeKey: $request->checkId.':fake',
                ),
            ]);
        }
    };
}

test('reports deterministic findings as json and exits with blocking status', function () {
    $exit = Artisan::call('code-quality:check', [
        'paths' => ['tests/Fixtures/CodeQuality/EnvOutsideConfig.php'],
        '--checks' => ['deterministic.config-env'],
        '--format' => 'json',
    ]);

    $report = codeQualityJsonOutput();

    expect($exit)->toBe(1)
        ->and($report['schema_version'])->toBe(1)
        ->and($report['status'])->toBe('failed')
        ->and($report['findings'])->toHaveCount(1)
        ->and($report['findings'][0]['check_id'])->toBe('deterministic.config-env')
        ->and($report['findings'][0]['blocking'])->toBeTrue();
});

test('unknown check ids fail fast', function () {
    $exit = Artisan::call('code-quality:check', [
        'paths' => ['tests/Fixtures/CodeQuality/CleanService.php'],
        '--checks' => ['deterministic.nope'],
        '--format' => 'json',
    ]);

    expect($exit)->toBe(2)
        ->and(Artisan::output())->toContain('Unknown code-quality check or group');
});

test('all with no ai excludes ai checks', function () {
    $reviewer = fakeAiReviewer();
    app()->instance(AiReviewer::class, $reviewer);

    $exit = Artisan::call('code-quality:check', [
        'paths' => ['tests/Fixtures/CodeQuality/CleanService.php'],
        '--checks' => ['all'],
        '--no-ai' => true,
        '--format' => 'json',
    ]);

    $report = codeQualityJsonOutput();

    expect($exit)->toBe(0)
        ->and($reviewer->requests)->toHaveCount(0)
        ->and($report['configuration']['checks'])->not->toContain('ai.architecture');
});

test('ai checks call the configured reviewer when enabled', function () {
    $reviewer = fakeAiReviewer();
    app()->instance(AiReviewer::class, $reviewer);

    $exit = Artisan::call('code-quality:check', [
        'paths' => ['app/Services/CodeQuality/CodeQualityRunOptions.php'],
        '--checks' => ['ai.architecture'],
        '--ai' => true,
        '--format' => 'json',
    ]);

    $report = codeQualityJsonOutput();

    expect($exit)->toBe(1)
        ->and($reviewer->requests)->toHaveCount(1)
        ->and($report['findings'][0]['source'])->toBe('ai')
        ->and($report['findings'][0]['blocking'])->toBeTrue();
});

test('json output can be written to a report file', function () {
    $output = 'storage/app/code-quality/test-command-report.json';
    @unlink(base_path($output));

    $exit = Artisan::call('code-quality:check', [
        'paths' => ['tests/Fixtures/CodeQuality/EnvOutsideConfig.php'],
        '--checks' => ['deterministic.config-env'],
        '--format' => 'json',
        '--output' => $output,
    ]);

    $written = json_decode((string) file_get_contents(base_path($output)), true, flags: JSON_THROW_ON_ERROR);

    expect($exit)->toBe(1)
        ->and($written['run_id'])->toBe(codeQualityJsonOutput()['run_id'])
        ->and($written['findings'][0]['check_id'])->toBe('deterministic.config-env');

    @unlink(base_path($output));
});

test('advisory findings below the failure threshold do not fail the command', function () {
    $exit = Artisan::call('code-quality:check', [
        'paths' => ['tests/Fixtures/CodeQuality/frontend-hardcoded-route.tsx'],
        '--checks' => ['deterministic.frontend-routes'],
        '--format' => 'json',
        '--fail-on' => 'error',
    ]);

    $report = codeQualityJsonOutput();

    expect($exit)->toBe(0)
        ->and($report['status'])->toBe('passed')
        ->and($report['findings'][0]['severity'])->toBe('warning')
        ->and($report['findings'][0]['blocking'])->toBeFalse();
});

test('configured ai-free path boundaries flag ai infrastructure references', function () {
    config(['code_quality.domain.ai_free_path_keywords' => ['Heartbeat']]);

    $exit = Artisan::call('code-quality:check', [
        'paths' => ['tests/Fixtures/CodeQuality/HeartbeatAiReference.php'],
        '--checks' => ['deterministic.ai-free-boundaries'],
        '--format' => 'json',
    ]);

    $report = codeQualityJsonOutput();

    expect($exit)->toBe(1)
        ->and($report['findings'][0]['check_id'])->toBe('deterministic.ai-free-boundaries')
        ->and($report['findings'][0]['category'])->toBe('ai-boundary');
});

test('checks with no matching scoped files are reported as skipped', function () {
    $exit = Artisan::call('code-quality:check', [
        'paths' => ['tests/Fixtures/CodeQuality/CleanService.php'],
        '--checks' => ['deterministic.dependencies'],
        '--format' => 'json',
    ]);

    $report = codeQualityJsonOutput();

    expect($exit)->toBe(0)
        ->and($report['summary']['skipped_checks'])->toBe(1)
        ->and($report['check_results'][0]['status'])->toBe('skipped');
});

test('checker failures return exit code three with a structured report', function () {
    app()->instance(AiReviewer::class, fakeAiReviewer(CodeQualityException::checkerFailed('fake checker failed')));

    $exit = Artisan::call('code-quality:check', [
        'paths' => ['app/Services/CodeQuality/CodeQualityRunOptions.php'],
        '--checks' => ['ai.architecture'],
        '--ai' => true,
        '--format' => 'json',
    ]);

    $report = codeQualityJsonOutput();

    expect($exit)->toBe(3)
        ->and($report['status'])->toBe('errored')
        ->and($report['check_results'][0]['status'])->toBe('errored');
});

test('timeouts return exit code four with a structured report', function () {
    app()->instance(AiReviewer::class, fakeAiReviewer(CodeQualityException::timedOut('fake timeout')));

    $exit = Artisan::call('code-quality:check', [
        'paths' => ['app/Services/CodeQuality/CodeQualityRunOptions.php'],
        '--checks' => ['ai.architecture'],
        '--ai' => true,
        '--format' => 'json',
    ]);

    $report = codeQualityJsonOutput();

    expect($exit)->toBe(4)
        ->and($report['status'])->toBe('timed_out')
        ->and($report['check_results'][0]['status'])->toBe('timed_out');
});

test('ai unavailable returns exit code five', function () {
    app()->instance(AiReviewer::class, fakeAiReviewer(CodeQualityException::aiUnavailable('fake codex missing')));

    $exit = Artisan::call('code-quality:check', [
        'paths' => ['app/Services/CodeQuality/CodeQualityRunOptions.php'],
        '--checks' => ['ai.architecture'],
        '--ai' => true,
        '--format' => 'json',
    ]);

    expect($exit)->toBe(5)
        ->and(Artisan::output())->toContain('fake codex missing');
});

test('codex ai output schema requires every declared finding property', function () {
    $method = new ReflectionMethod(CodexAiRunner::class, 'schema');
    $schema = $method->invoke(app(CodexAiRunner::class));
    $item = $schema['properties']['findings']['items'];

    expect($item['additionalProperties'])->toBeFalse()
        ->and($item['required'])->toEqualCanonicalizing(array_keys($item['properties']));
});

test('codex ai runner normalizes finding check ids to the source check', function () {
    $runner = new CodexAiRunner(new class extends ProcessRunner
    {
        public function run(array $command, string $workingDirectory, int $timeoutSeconds, array $environment = []): ProcessResult
        {
            return new ProcessResult(
                command: $command,
                exitCode: 0,
                output: json_encode([
                    'findings' => [[
                        'id' => 'F1',
                        'check_id' => 'made-up-check',
                        'source' => 'deterministic',
                        'severity' => 'warning',
                        'confidence' => 'high',
                        'category' => 'architecture',
                        'title' => 'Bad check id',
                        'description' => 'The model returned a wrong check id.',
                        'file' => null,
                        'line' => null,
                        'end_line' => null,
                        'symbol' => null,
                        'evidence' => null,
                        'recommendation' => 'Normalize it.',
                        'docs' => [],
                        'dedupe_key' => null,
                    ]],
                ], JSON_THROW_ON_ERROR),
                errorOutput: '',
                durationMs: 1,
            );
        }
    }, app('files'));

    $result = $runner->review(new AiReviewRequest(
        runId: 'cqr_test',
        checkId: 'ai.architecture',
        checkName: 'architecture review',
        basePath: base_path(),
        files: ['app/Services/CodeQuality/Runners/CodexAiRunner.php'],
        checklistExcerpt: 'Architecture',
        deterministicFindings: [],
        model: 'test-model',
        timeoutSeconds: 1,
    ));

    expect($result->findings)->toHaveCount(1)
        ->and($result->findings[0]->checkId)->toBe('ai.architecture')
        ->and($result->findings[0]->source)->toBe('ai');
});
