<?php

namespace App\Services\CodeQuality;

use App\Services\CodeQuality\Runners\ProcessRunner;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use SplFileInfo;

class ScopeResolver
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly ProcessRunner $processes,
    ) {}

    public function resolve(CodeQualityRunOptions $options): CodeQualityScope
    {
        $basePath = base_path();
        $inputPaths = $options->paths;
        $files = $inputPaths === []
            ? $this->resolveImplicitFiles($options, $basePath)
            : $this->resolveExplicitFiles($inputPaths, $options, $basePath);

        $files = $this->excludeFiles($files, $options);

        if ($files === [] && ! $options->hasExplicitAllChecks()) {
            throw CodeQualityException::invalidInput('No files matched the requested code-quality scope.');
        }

        return new CodeQualityScope(
            basePath: $basePath,
            inputPaths: $inputPaths,
            files: $files,
            base: $options->base,
            changedOnly: $options->changed,
            stagedOnly: $options->staged,
        );
    }

    /**
     * @param  list<string>  $inputPaths
     * @return list<string>
     */
    private function resolveExplicitFiles(array $inputPaths, CodeQualityRunOptions $options, string $basePath): array
    {
        $resolved = [];

        foreach ($inputPaths as $path) {
            $absolutePath = $this->absolutePath($path, $basePath);

            if (! $this->files->exists($absolutePath)) {
                throw CodeQualityException::invalidInput("Path does not exist: {$path}");
            }

            if ($this->files->isFile($absolutePath)) {
                $resolved[] = $this->relativePath($absolutePath, $basePath);

                continue;
            }

            foreach ($this->files->allFiles($absolutePath) as $file) {
                $resolved[] = $this->relativePath($file->getPathname(), $basePath);
            }
        }

        return $this->uniqueSorted($resolved);
    }

    /**
     * @return list<string>
     */
    private function resolveImplicitFiles(CodeQualityRunOptions $options, string $basePath): array
    {
        if ($options->staged) {
            return $this->gitFiles(['git', 'diff', '--cached', '--name-only', '--diff-filter=ACMR'], $basePath);
        }

        if ($options->changed || $this->isInteractive()) {
            $command = ['git', 'diff', '--name-only', '--diff-filter=ACMR'];

            if ($options->base !== null && $options->base !== '') {
                $command[] = $options->base;
                $command[] = '--';
            }

            $files = [
                ...$this->gitFiles($command, $basePath),
                ...$this->gitFiles(['git', 'ls-files', '--others', '--exclude-standard'], $basePath),
            ];

            if ($files !== []) {
                return $this->uniqueSorted($files);
            }
        }

        if ($options->hasExplicitAllChecks()) {
            return $this->gitFiles(['git', 'ls-files', '--cached', '--others', '--exclude-standard'], $basePath);
        }

        throw CodeQualityException::invalidInput('Pass paths, --changed, --staged, or --checks=all when no changed files are available.');
    }

    /**
     * @param  list<string>  $files
     * @return list<string>
     */
    private function excludeFiles(array $files, CodeQualityRunOptions $options): array
    {
        $patterns = [
            ...config('code_quality.paths.exclude', []),
            ...$options->excludes,
        ];

        return array_values(array_filter(
            $files,
            fn (string $file): bool => ! $this->isExcluded($file, $patterns) && $this->files->isFile(base_path($file)),
        ));
    }

    /**
     * @param  list<string>  $command
     * @return list<string>
     */
    private function gitFiles(array $command, string $basePath): array
    {
        $result = $this->processes->run($command, $basePath, 30);

        if (! $result->successful()) {
            return [];
        }

        return $this->uniqueSorted(array_filter(array_map(
            fn (string $file): string => str_replace('\\', '/', trim($file)),
            preg_split('/\R/', $result->output) ?: [],
        )));
    }

    /**
     * @param  list<string>  $patterns
     */
    private function isExcluded(string $file, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            $pattern = trim((string) $pattern);

            if ($pattern === '') {
                continue;
            }

            if (Str::is($pattern, $file) || Str::startsWith($file, rtrim($pattern, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    private function absolutePath(string $path, string $basePath): string
    {
        $path = $path === '' ? '.' : $path;
        $candidate = str_starts_with($path, '/') ? $path : $basePath.'/'.$path;
        $realPath = realpath($candidate);

        if ($realPath === false) {
            return $candidate;
        }

        if (! Str::startsWith($realPath, $basePath)) {
            throw CodeQualityException::invalidInput("Path is outside the repository: {$path}");
        }

        return $realPath;
    }

    private function relativePath(string $path, string $basePath): string
    {
        $path = $path instanceof SplFileInfo ? $path->getPathname() : $path;
        $path = str_replace('\\', '/', $path);
        $basePath = str_replace('\\', '/', $basePath);

        return ltrim(Str::after($path, $basePath), '/');
    }

    /**
     * @param  array<int, string|false|null>  $files
     * @return list<string>
     */
    private function uniqueSorted(array $files): array
    {
        $files = array_values(array_unique(array_filter(array_map(
            fn (string|false|null $file): string => str_replace('\\', '/', trim((string) $file)),
            $files,
        ))));

        sort($files);

        return $files;
    }

    private function isInteractive(): bool
    {
        return function_exists('posix_isatty') && posix_isatty(STDIN);
    }
}
