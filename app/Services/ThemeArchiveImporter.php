<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use ZipArchive;

class ThemeArchiveImporter
{
    public function import(Store $store, UploadedFile $archive, ?string $name = null): Theme
    {
        $zip = new ZipArchive;
        $opened = $zip->open($archive->getPathname());

        if ($opened !== true) {
            throw ValidationException::withMessages([
                'file' => 'The theme archive could not be opened.',
            ]);
        }

        try {
            $manifest = $this->manifest($zip);
            $entries = $this->entries($zip);

            if ($entries === []) {
                throw ValidationException::withMessages([
                    'file' => 'The theme archive must contain at least one theme file.',
                ]);
            }

            return DB::transaction(function () use ($entries, $manifest, $name, $store, $zip): Theme {
                $theme = Theme::withoutGlobalScopes()->create([
                    'store_id' => $store->id,
                    'name' => $this->themeName($name, $manifest),
                    'version' => $this->themeVersion($manifest),
                    'status' => ThemeStatus::Draft,
                    'published_at' => null,
                ]);

                foreach ($entries as $index => $path) {
                    $contents = $zip->getFromIndex($index);

                    if ($contents === false) {
                        throw ValidationException::withMessages([
                            'file' => "The theme file [{$path}] could not be read.",
                        ]);
                    }

                    $storageKey = "themes/{$store->id}/{$theme->id}/{$path}";
                    Storage::disk('local')->put($storageKey, $contents);

                    $theme->files()->create([
                        'path' => $path,
                        'storage_key' => $storageKey,
                        'sha256' => hash('sha256', $contents),
                        'byte_size' => strlen($contents),
                    ]);
                }

                $settings = $manifest['settings'] ?? [];
                $theme->settings()->create([
                    'settings_json' => is_array($settings) ? $settings : [],
                ]);

                return $theme->refresh()->load('settings')->loadCount('files');
            });
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(ZipArchive $zip): array
    {
        $contents = $zip->getFromName('theme.json');

        if ($contents === false) {
            throw ValidationException::withMessages([
                'file' => 'The theme archive must contain a theme.json manifest.',
            ]);
        }

        try {
            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'file' => 'The theme manifest must be valid JSON.',
            ]);
        }

        if (! is_array($manifest)) {
            throw ValidationException::withMessages([
                'file' => 'The theme manifest must be a JSON object.',
            ]);
        }

        return $manifest;
    }

    /**
     * @return array<int, string>
     */
    private function entries(ZipArchive $zip): array
    {
        $entries = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            $path = is_array($stat) ? $this->normalizePath((string) ($stat['name'] ?? '')) : null;

            if ($path === null || $path === 'theme.json') {
                continue;
            }

            $entries[$index] = $path;
        }

        return $entries;
    }

    private function normalizePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        if ($path === '' || str_ends_with($path, '/') || str_contains($path, "\0")) {
            return null;
        }

        $segments = explode('/', $path);

        if ($segments[0] === '__MACOSX' || in_array('..', $segments, true)) {
            throw ValidationException::withMessages([
                'file' => 'The theme archive contains an unsafe file path.',
            ]);
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function themeName(?string $name, array $manifest): string
    {
        $resolved = trim((string) ($name ?: ($manifest['name'] ?? 'Imported theme')));

        return Str::of($resolved === '' ? 'Imported theme' : $resolved)
            ->limit(255, '')
            ->toString();
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function themeVersion(array $manifest): ?string
    {
        $version = trim((string) ($manifest['version'] ?? ''));

        return $version === ''
            ? null
            : Str::of($version)->limit(255, '')->toString();
    }
}
