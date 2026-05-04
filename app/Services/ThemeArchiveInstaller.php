<?php

namespace App\Services;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ThemeArchiveInstaller
{
    /**
     * @var list<string>
     */
    private const REQUIRED_PATHS = [
        'layouts/storefront.blade.php',
        'sections/hero.blade.php',
        'sections/featured-products.blade.php',
    ];

    public function __construct(private ThemeSettingsService $settings) {}

    public function install(Store $store, UploadedFile $archive, ?string $name = null): Theme
    {
        [$files, $manifest] = $this->readArchive($archive);

        $this->validateStructure($files);

        $themeName = trim((string) ($name ?: data_get($manifest, 'name', '')));

        if ($themeName === '') {
            throw ValidationException::withMessages([
                'name' => __('The theme archive manifest must include a name.'),
            ]);
        }

        $version = trim((string) data_get($manifest, 'version', '1.0.0'));
        $settings = data_get($manifest, 'settings_json', data_get($manifest, 'settings'));

        if (mb_strlen($themeName) > 255) {
            throw ValidationException::withMessages([
                'name' => __('The theme name may not be greater than 255 characters.'),
            ]);
        }

        if (mb_strlen($version) > 255) {
            throw ValidationException::withMessages([
                'file' => __('The theme archive manifest version may not be greater than 255 characters.'),
            ]);
        }

        return DB::transaction(function () use ($files, $settings, $store, $themeName, $version): Theme {
            $theme = Theme::withoutGlobalScopes()->create([
                'store_id' => $store->getKey(),
                'name' => $themeName,
                'version' => $version !== '' ? $version : null,
                'status' => ThemeStatus::Draft,
                'published_at' => null,
            ]);

            foreach ($files as $path => $contents) {
                $storageKey = "themes/{$theme->getKey()}/{$path}";

                Storage::disk('local')->put($storageKey, $contents);

                ThemeFile::withoutGlobalScopes()->create([
                    'theme_id' => $theme->getKey(),
                    'path' => $path,
                    'storage_key' => $storageKey,
                    'sha256' => hash('sha256', $contents),
                    'byte_size' => strlen($contents),
                ]);
            }

            ThemeSettings::withoutGlobalScopes()->create([
                'theme_id' => $theme->getKey(),
                'settings_json' => is_array($settings) && ! array_is_list($settings)
                    ? $settings
                    : $this->settings->defaultsForStore($store),
                'updated_at' => now(),
            ]);

            return $theme->load('settings')->loadCount('files');
        });
    }

    /**
     * @return list<string>
     */
    public static function requiredPaths(): array
    {
        return self::REQUIRED_PATHS;
    }

    /**
     * @return array{0: array<string, string>, 1: array<string, mixed>}
     */
    private function readArchive(UploadedFile $archive): array
    {
        $zip = new ZipArchive;
        $realPath = $archive->getRealPath();

        if (! is_string($realPath) || $zip->open($realPath) !== true) {
            throw ValidationException::withMessages([
                'file' => __('The theme archive could not be opened.'),
            ]);
        }

        $files = [];

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);

                if (! is_string($name)) {
                    continue;
                }

                $path = $this->normalizePath($name);

                if ($path === null) {
                    continue;
                }

                $contents = $zip->getFromIndex($index);

                if (! is_string($contents)) {
                    throw ValidationException::withMessages([
                        'file' => __('The theme archive contains an unreadable file.'),
                    ]);
                }

                $files[$path] = $contents;
            }
        } finally {
            $zip->close();
        }

        $files = $this->stripRootDirectory($files);
        $manifestPath = array_key_exists('theme.json', $files)
            ? 'theme.json'
            : (array_key_exists('manifest.json', $files) ? 'manifest.json' : null);

        if ($manifestPath === null) {
            throw ValidationException::withMessages([
                'file' => __('The theme archive must contain a theme.json manifest.'),
            ]);
        }

        $manifest = json_decode($files[$manifestPath], true);

        if (! is_array($manifest) || array_is_list($manifest)) {
            throw ValidationException::withMessages([
                'file' => __('The theme archive manifest must be a valid JSON object.'),
            ]);
        }

        return [$files, $manifest];
    }

    private function normalizePath(string $name): ?string
    {
        $path = str_replace('\\', '/', $name);

        if ($path === '' || str_ends_with($path, '/')) {
            return null;
        }

        if (str_starts_with($path, '/') || str_starts_with($path, '__MACOSX/')) {
            throw ValidationException::withMessages([
                'file' => __('The theme archive contains an invalid file path.'),
            ]);
        }

        $segments = explode('/', $path);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw ValidationException::withMessages([
                    'file' => __('The theme archive contains an invalid file path.'),
                ]);
            }
        }

        if (end($segments) === '.DS_Store') {
            return null;
        }

        if (strlen($path) > 255) {
            throw ValidationException::withMessages([
                'file' => __('The theme archive contains a file path that is too long.'),
            ]);
        }

        return $path;
    }

    /**
     * @param  array<string, string>  $files
     * @return array<string, string>
     */
    private function stripRootDirectory(array $files): array
    {
        if ($files === []) {
            return $files;
        }

        $paths = array_keys($files);

        if (! collect($paths)->every(fn (string $path): bool => str_contains($path, '/'))) {
            return $files;
        }

        $root = explode('/', $paths[0], 2)[0];

        if (! collect($paths)->every(fn (string $path): bool => str_starts_with($path, "{$root}/"))) {
            return $files;
        }

        return collect($files)
            ->mapWithKeys(fn (string $contents, string $path): array => [substr($path, strlen($root) + 1) => $contents])
            ->all();
    }

    /**
     * @param  array<string, string>  $files
     */
    private function validateStructure(array $files): void
    {
        $missing = array_values(array_diff(self::REQUIRED_PATHS, array_keys($files)));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => __('The theme archive is missing required file: :path', ['path' => $missing[0]]),
            ]);
        }
    }
}
