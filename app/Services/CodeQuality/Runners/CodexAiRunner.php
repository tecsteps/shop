<?php

namespace App\Services\CodeQuality\Runners;

use App\Services\CodeQuality\AiReviewRequest;
use App\Services\CodeQuality\AiReviewResult;
use App\Services\CodeQuality\CodeQualityException;
use App\Services\CodeQuality\Contracts\AiReviewer;
use App\Services\CodeQuality\Findings\Finding;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class CodexAiRunner implements AiReviewer
{
    public function __construct(
        private readonly ProcessRunner $processes,
        private readonly Filesystem $files,
    ) {}

    public function review(AiReviewRequest $request): AiReviewResult
    {
        $schemaPath = $this->ensureSchema();
        $runDirectory = storage_path("app/code-quality/runs/{$request->runId}");
        $this->files->ensureDirectoryExists($runDirectory);
        $outputPath = "{$runDirectory}/".str_replace('.', '-', $request->checkId).'.json';

        $result = $this->processes->run(
            command: $this->command($request, $schemaPath, $outputPath),
            workingDirectory: $request->basePath,
            timeoutSeconds: $request->timeoutSeconds,
            environment: $this->safeEnvironment(),
        );

        if (! $result->successful()) {
            throw CodeQualityException::aiUnavailable(trim($result->errorOutput) !== ''
                ? 'Codex AI review failed: '.Str::limit(trim($result->errorOutput), 500)
                : 'Codex AI review failed with exit code '.$result->exitCode);
        }

        $rawOutput = $this->files->exists($outputPath)
            ? $this->files->get($outputPath)
            : $result->output;

        $decoded = json_decode($rawOutput, true);

        if (! is_array($decoded)) {
            throw CodeQualityException::checkerFailed("Codex AI review did not return parseable JSON for {$request->checkId}.");
        }

        $items = is_array($decoded['findings'] ?? null) ? $decoded['findings'] : $decoded;
        $findings = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $findings[] = Finding::fromArray([
                ...$item,
                'check_id' => $request->checkId,
                'source' => 'ai',
            ], $request->checkId, 'ai');
        }

        return new AiReviewResult($findings, $rawOutput);
    }

    /**
     * @return list<string>
     */
    private function command(AiReviewRequest $request, string $schemaPath, string $outputPath): array
    {
        $binary = (string) config('code_quality.ai.binary', 'codex');

        return [
            $binary,
            'exec',
            '--ephemeral',
            '--sandbox',
            'read-only',
            '-C',
            $request->basePath,
            '--model',
            $request->model,
            '-c',
            'approval_policy="never"',
            '-c',
            'model_reasoning_effort="'.addcslashes((string) config('code_quality.ai.reasoning_effort', 'low'), '"').'"',
            '-c',
            'model_verbosity="'.addcslashes((string) config('code_quality.ai.verbosity', 'low'), '"').'"',
            '--output-schema',
            $schemaPath,
            '--output-last-message',
            $outputPath,
            '--color',
            'never',
            $this->prompt($request),
        ];
    }

    private function prompt(AiReviewRequest $request): string
    {
        $deterministicFindings = array_map(
            fn (Finding $finding): array => $finding->toArray(),
            $request->deterministicFindings,
        );

        return implode("\n\n", [
            "You are reviewing Laravel application code for {$request->checkName}.",
            'Return only JSON matching the provided schema.',
            'Do not edit files. Do not ask to run commands.',
            'Do not repeat deterministic findings unless you add a separate risk.',
            'No compliments. Findings only.',
            'Review scope:',
            implode("\n", $request->files),
            'Relevant checklist:',
            $request->checklistExcerpt,
            'Deterministic findings already found:',
            json_encode($deterministicFindings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'Files:',
            $this->fileExcerpts($request),
            'Find only concrete issues with evidence. If there are no findings, return {"findings":[]}.',
        ]);
    }

    private function fileExcerpts(AiReviewRequest $request): string
    {
        $chunks = [];
        $remainingBytes = (int) config('code_quality.ai.max_prompt_file_bytes', 120_000);

        foreach ($request->files as $file) {
            if ($remainingBytes <= 0) {
                break;
            }

            $path = $request->basePath.'/'.$file;

            if (! $this->files->isFile($path) || ! $this->files->isReadable($path)) {
                continue;
            }

            $content = $this->files->get($path);
            $excerpt = Str::limit($content, min(30_000, $remainingBytes), "\n...[truncated]");
            $remainingBytes -= strlen($excerpt);
            $chunks[] = "### {$file}\n```text\n{$excerpt}\n```";
        }

        return implode("\n\n", $chunks);
    }

    private function ensureSchema(): string
    {
        $path = storage_path('app/code-quality/schemas/ai-finding-report.schema.json');
        $this->files->ensureDirectoryExists(dirname($path));

        $this->files->put($path, json_encode($this->schema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'findings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'id' => ['type' => 'string'],
                            'check_id' => ['type' => 'string'],
                            'source' => ['type' => 'string', 'enum' => ['ai']],
                            'severity' => ['type' => 'string', 'enum' => ['info', 'warning', 'error', 'critical']],
                            'confidence' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                            'category' => ['type' => 'string'],
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'file' => ['type' => ['string', 'null']],
                            'line' => ['type' => ['integer', 'null']],
                            'end_line' => ['type' => ['integer', 'null']],
                            'symbol' => ['type' => ['string', 'null']],
                            'evidence' => ['type' => ['string', 'null']],
                            'recommendation' => ['type' => 'string'],
                            'docs' => ['type' => 'array', 'items' => ['type' => 'string']],
                            'dedupe_key' => ['type' => ['string', 'null']],
                        ],
                        'required' => [
                            'id',
                            'check_id',
                            'source',
                            'severity',
                            'confidence',
                            'category',
                            'title',
                            'description',
                            'file',
                            'line',
                            'end_line',
                            'symbol',
                            'evidence',
                            'recommendation',
                            'docs',
                            'dedupe_key',
                        ],
                    ],
                ],
            ],
            'required' => ['findings'],
        ];
    }

    /**
     * @return array<string, string|false>
     */
    private function safeEnvironment(): array
    {
        $allowed = ['PATH', 'HOME', 'USER', 'LOGNAME', 'SHELL', 'TMPDIR', 'TEMP', 'TMP', 'TERM', 'CODEX_HOME', 'XDG_CONFIG_HOME'];
        $environment = [];

        foreach (array_keys([...$_SERVER, ...$_ENV]) as $key) {
            if (in_array($key, $allowed, true) && ! $this->isSecretLike($key)) {
                $value = getenv($key);

                if ($value !== false) {
                    $environment[$key] = $value;
                }

                continue;
            }

            $environment[$key] = false;
        }

        return $environment;
    }

    private function isSecretLike(string $key): bool
    {
        return preg_match('/(?:SECRET|TOKEN|PASSWORD|PASS|KEY|COOKIE|AUTH|CREDENTIAL|DATABASE_URL|OPENROUTER|STRIPE|AWS)/i', $key) === 1;
    }
}
