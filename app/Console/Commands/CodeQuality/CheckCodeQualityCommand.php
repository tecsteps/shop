<?php

namespace App\Console\Commands\CodeQuality;

use App\Services\CodeQuality\CodeQualityException;
use App\Services\CodeQuality\CodeQualityPipeline;
use App\Services\CodeQuality\CodeQualityRunOptions;
use App\Services\CodeQuality\Output\JsonlReportWriter;
use App\Services\CodeQuality\Output\JsonReportWriter;
use App\Services\CodeQuality\Output\PrettyReportWriter;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Throwable;

class CheckCodeQualityCommand extends Command
{
    protected $signature = 'code-quality:check
    {paths?* : Files or directories to check. Defaults to changed files, then repo-wide fallback.}
    {--checks=* : Check IDs or groups to run. Use all for every enabled check.}
    {--exclude=* : Glob patterns to exclude.}
    {--changed : Scope to changed files from git diff.}
    {--staged : Scope to staged files from git diff --cached.}
    {--base= : Base git ref for changed-file detection.}
    {--format=pretty : Output format: pretty, json, jsonl.}
    {--output= : Optional path to write the normalized report.}
    {--fail-on=error : Minimum severity that returns a failing exit code: info, warning, error, critical.}
    {--ai : Enable AI checks.}
    {--no-ai : Disable AI checks even when checks=all.}
    {--ai-model= : Override config("code_quality.ai.model") for this run.}
    {--ai-concurrency= : Override config("code_quality.ai.concurrency").}
    {--timeout= : Total command timeout in seconds.}
    {--check-timeout= : Per-check timeout in seconds.}
    {--profile=default : Named check profile.}';

    protected $description = 'Run the Laravel code-quality check pipeline without modifying source files';

    protected $help = 'Runs read-only deterministic and optional AI review checks for a check -> fix -> check loop. The command reports findings only; it never edits source files.';

    /**
     * Execute the console command.
     */
    public function handle(
        CodeQualityPipeline $pipeline,
        JsonReportWriter $json,
        JsonlReportWriter $jsonl,
        PrettyReportWriter $pretty,
        Filesystem $files,
    ): int {
        try {
            $options = $this->optionsDto();
            $report = $pipeline->run($options);
            $output = match ($options->format) {
                'json' => $json->write($report),
                'jsonl' => $jsonl->write($report),
                'pretty' => $pretty->write($report),
                default => throw CodeQualityException::invalidInput("Unsupported output format: {$options->format}"),
            };

            if ($options->output !== null && $options->output !== '') {
                $outputPath = $this->outputPath($options->output);
                $files->ensureDirectoryExists(dirname($outputPath));
                $files->put($outputPath, $json->write($report));
            }

            $this->output->write($output);

            return $report->exitCode();
        } catch (CodeQualityException $exception) {
            $this->components->error($exception->getMessage());

            return $exception->exitCode();
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function optionsDto(): CodeQualityRunOptions
    {
        $format = (string) $this->option('format');
        $failOn = (string) $this->option('fail-on');

        if (! in_array($format, ['pretty', 'json', 'jsonl'], true)) {
            throw CodeQualityException::invalidInput("Unsupported output format: {$format}");
        }

        if (! in_array($failOn, ['info', 'warning', 'error', 'critical'], true)) {
            throw CodeQualityException::invalidInput("Unsupported --fail-on severity: {$failOn}");
        }

        $timeout = $this->positiveIntOption('timeout', (int) config('code_quality.defaults.timeout_seconds', 600));
        $checkTimeout = $this->positiveIntOption('check-timeout', (int) config('code_quality.defaults.check_timeout_seconds', 180));
        $aiConcurrency = $this->option('ai-concurrency') === null
            ? null
            : $this->positiveIntOption('ai-concurrency', (int) config('code_quality.ai.concurrency', 3));

        return new CodeQualityRunOptions(
            paths: array_values(array_map('strval', (array) $this->argument('paths'))),
            checks: array_values(array_map('strval', (array) $this->option('checks'))),
            excludes: array_values(array_map('strval', (array) $this->option('exclude'))),
            changed: (bool) $this->option('changed'),
            staged: (bool) $this->option('staged'),
            base: $this->option('base') === null ? null : (string) $this->option('base'),
            format: $format,
            output: $this->option('output') === null ? null : (string) $this->option('output'),
            failOn: $failOn,
            ai: (bool) $this->option('ai'),
            noAi: (bool) $this->option('no-ai'),
            aiModel: $this->option('ai-model') === null ? null : (string) $this->option('ai-model'),
            aiConcurrency: $aiConcurrency,
            timeoutSeconds: $timeout,
            checkTimeoutSeconds: $checkTimeout,
            profile: (string) $this->option('profile'),
        );
    }

    private function positiveIntOption(string $name, int $default): int
    {
        $value = $this->option($name);

        if ($value === null || $value === '') {
            return $default;
        }

        if (! is_numeric($value) || (int) $value <= 0) {
            throw CodeQualityException::invalidInput("--{$name} must be a positive integer.");
        }

        return (int) $value;
    }

    private function outputPath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            $directory = realpath(dirname($path)) ?: dirname($path);

            if (! str_starts_with($directory, base_path())) {
                throw CodeQualityException::invalidInput('The --output path must stay inside the repository.');
            }

            return $path;
        }

        return base_path($path);
    }
}
