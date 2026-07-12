<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Theme;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;
use ZipArchive;

final class ThemeArchiveService
{
    private const MAX_ENTRIES = 500;

    private const MAX_UNCOMPRESSED_BYTES = 50 * 1024 * 1024;

    public function install(Store $store, UploadedFile $file, ?string $name = null): Theme
    {
        [$manifest, $files] = $this->inspect($file);
        $themeName = trim((string) ($name ?: ($manifest['name'] ?? '')));
        $version = trim((string) ($manifest['version'] ?? '1.0.0'));
        if ($themeName === '' || mb_strlen($themeName) > 255) {
            throw ValidationException::withMessages(['file' => 'The theme manifest must contain a valid name.']);
        }
        if (preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) !== 1) {
            throw ValidationException::withMessages(['file' => 'The theme manifest version must use semantic versioning.']);
        }

        $directory = null;
        try {
            return DB::transaction(function () use ($store, $themeName, $version, $manifest, $files, &$directory): Theme {
                $theme = Theme::withoutGlobalScopes()->create([
                    'store_id' => $store->id,
                    'name' => $themeName,
                    'version' => $version,
                    'status' => 'draft',
                ]);
                $directory = "themes/{$store->id}/{$theme->id}";
                foreach ($files as $path => $contents) {
                    $storageKey = "{$directory}/{$path}";
                    Storage::disk('local')->put($storageKey, $contents);
                    $theme->files()->create([
                        'path' => $path,
                        'storage_key' => $storageKey,
                        'sha256' => hash('sha256', $contents),
                        'byte_size' => strlen($contents),
                    ]);
                }
                $theme->settings()->create(['settings_json' => (array) ($manifest['settings'] ?? [])]);

                return $theme->load(['files', 'settings']);
            });
        } catch (Throwable $exception) {
            if ($directory !== null) {
                Storage::disk('local')->deleteDirectory($directory);
            }
            throw $exception;
        }
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    private function inspect(UploadedFile $file): array
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages(['file' => 'The uploaded theme is not a readable ZIP archive.']);
        }

        try {
            if ($zip->numFiles < 2 || $zip->numFiles > self::MAX_ENTRIES) {
                throw ValidationException::withMessages(['file' => 'The theme archive has an invalid number of files.']);
            }
            $files = [];
            $uncompressedBytes = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if (! is_array($stat)) {
                    throw ValidationException::withMessages(['file' => 'The theme archive contains an unreadable entry.']);
                }
                $path = str_replace('\\', '/', (string) $stat['name']);
                if (str_ends_with($path, '/')) {
                    continue;
                }
                if (! $this->safePath($path)) {
                    throw ValidationException::withMessages(['file' => "The theme archive contains an unsafe path: {$path}."]);
                }
                $uncompressedBytes += (int) ($stat['size'] ?? 0);
                if ($uncompressedBytes > self::MAX_UNCOMPRESSED_BYTES) {
                    throw ValidationException::withMessages(['file' => 'The expanded theme archive is too large.']);
                }
                $contents = $zip->getFromIndex($index);
                if (! is_string($contents)) {
                    throw ValidationException::withMessages(['file' => "The theme file {$path} could not be read."]);
                }
                $files[$path] = $contents;
            }
        } finally {
            $zip->close();
        }

        if (! isset($files['theme.json'])) {
            throw ValidationException::withMessages(['file' => 'The theme archive must contain theme.json.']);
        }
        try {
            $manifest = json_decode($files['theme.json'], true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['file' => 'The theme manifest is invalid JSON.']);
        }
        if (! is_array($manifest) || array_is_list($manifest)) {
            throw ValidationException::withMessages(['file' => 'The theme manifest must be a JSON object.']);
        }
        $required = (array) ($manifest['required_templates'] ?? ['templates/index.blade.php']);
        foreach ($required as $template) {
            if (! is_string($template) || ! str_starts_with($template, 'templates/') || ! isset($files[$template])) {
                throw ValidationException::withMessages(['file' => 'The theme archive is missing a required template.']);
            }
        }

        return [$manifest, $files];
    }

    private function safePath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, "\0") || str_contains($path, '../')) {
            return false;
        }

        return preg_match('/\.(?:json|blade\.php|css|js|svg|png|jpe?g|gif|webp|avif|woff2?)$/i', $path) === 1;
    }
}
