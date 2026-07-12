<?php

namespace App\Services\CodeQuality;

use Illuminate\Support\Str;

class CodeQualityScope
{
    /**
     * @param  list<string>  $inputPaths
     * @param  list<string>  $files
     */
    public function __construct(
        public readonly string $basePath,
        public readonly array $inputPaths,
        public readonly array $files,
        public readonly ?string $base,
        public readonly bool $changedOnly,
        public readonly bool $stagedOnly,
    ) {}

    /**
     * @param  list<string>  $areas
     * @return list<string>
     */
    public function filesForAreas(array $areas): array
    {
        return array_values(array_filter(
            $this->files,
            fn (string $file): bool => in_array($this->areaFor($file), $areas, true),
        ));
    }

    public function areaFor(string $file): string
    {
        return match (true) {
            Str::is('app/**', $file), Str::is('bootstrap/**', $file), Str::is('config/**', $file), Str::is('routes/**', $file), Str::is('database/**', $file) => 'php_backend',
            Str::is('tests/**', $file) => 'php_tests',
            Str::is('resources/js/**', $file) => 'frontend',
            Str::is('resources/views/**', $file) => 'blade',
            Str::is('worker/src/**', $file), Str::is('worker/tests/**', $file) => 'worker',
            Str::is('packages/check-spec/**', $file) => 'check_spec',
            Str::is('resources/prompts/**', $file) => 'prompts',
            Str::is('specs/**', $file), Str::is('docs/**', $file) => 'specs_docs',
            $this->isConfigFile($file) => 'config',
            default => 'other',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'paths' => $this->inputPaths,
            'files' => $this->files,
            'base' => $this->base,
            'changed_only' => $this->changedOnly,
            'staged_only' => $this->stagedOnly,
        ];
    }

    private function isConfigFile(string $file): bool
    {
        return Str::is([
            'composer.json',
            'composer.lock',
            'package.json',
            'package-lock.json',
            'pnpm-lock.yaml',
            'yarn.lock',
            'vite.config.*',
            'tsconfig.*',
            'phpstan.*',
            'pint.*',
            'eslint.config.*',
        ], $file);
    }
}
