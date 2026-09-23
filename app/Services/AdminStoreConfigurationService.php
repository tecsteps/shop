<?php

namespace App\Services;

use App\Models\Page;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\Theme;
use App\Support\HandleGenerator;
use App\Support\HtmlSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use ZipArchive;

class AdminStoreConfigurationService
{
    private const MAX_THEME_ARCHIVE_BYTES = 50 * 1024 * 1024;

    private const MAX_THEME_FILES = 500;

    public function __construct(private HandleGenerator $handles, private HtmlSanitizer $sanitizer) {}

    /** @param array{status?: string, page?: int, per_page?: int} $filters
     * @return array{data: list<array<string, mixed>>, meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    public function listPages(Store $store, array $filters): array
    {
        $perPage = $filters['per_page'] ?? 25;
        $pages = Page::query()
            ->where('store_id', $store->getKey())
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->orderBy('title')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $filters['page'] ?? 1);

        return [
            'data' => $pages->getCollection()->map(fn (Page $page): array => $this->pageResource($page))->all(),
            'meta' => [
                'current_page' => $pages->currentPage(),
                'per_page' => $pages->perPage(),
                'total' => $pages->total(),
                'last_page' => $pages->lastPage(),
            ],
        ];
    }

    /** @param array{title: string, handle?: ?string, body_html?: ?string, status?: string} $attributes */
    public function createPage(Store $store, array $attributes): Page
    {
        $status = $attributes['status'] ?? 'draft';
        $handle = ($attributes['handle'] ?? null) ?: $this->handles->generate($attributes['title'], 'pages', (int) $store->getKey());

        return Page::query()->create([
            'store_id' => $store->getKey(),
            'title' => $attributes['title'],
            'handle' => $handle,
            'body_html' => $this->sanitizer->sanitize($attributes['body_html'] ?? null),
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]);
    }

    /** @param array{title?: string, handle?: ?string, body_html?: ?string, status?: string} $attributes */
    public function updatePage(Page $page, array $attributes): Page
    {
        $status = $attributes['status'] ?? $page->status;
        $updated = [];

        foreach (['title', 'body_html'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $updated[$field] = $field === 'body_html'
                    ? $this->sanitizer->sanitize($attributes[$field])
                    : $attributes[$field];
            }
        }

        if (array_key_exists('handle', $attributes)) {
            $updated['handle'] = $attributes['handle'] ?: $this->handles->generate(
                $attributes['title'] ?? $page->title,
                'pages',
                (int) $page->store_id,
                (int) $page->getKey(),
            );
        }

        if (array_key_exists('status', $attributes)) {
            $updated['status'] = $status;
            $updated['published_at'] = $status === 'published' ? ($page->published_at ?? now()) : null;
        }

        $page->update($updated);

        return $page->refresh();
    }

    /** @return array<string, mixed> */
    public function pageResource(Page $page): array
    {
        return [
            'id' => $page->getKey(),
            'store_id' => $page->store_id,
            'title' => $page->title,
            'handle' => $page->handle,
            'status' => $page->status,
            'published_at' => $page->published_at?->toISOString(),
            'created_at' => $page->created_at?->toISOString(),
            'updated_at' => $page->updated_at?->toISOString(),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function listShippingZones(Store $store): array
    {
        return ShippingZone::query()
            ->where('store_id', $store->getKey())
            ->with(['rates' => fn ($query) => $query->orderBy('id')])
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (ShippingZone $zone): array => $this->shippingZoneResource($zone))
            ->all();
    }

    /** @param list<string> $countries */
    public function countryOverlap(Store $store, array $countries, ?int $excludeZoneId = null): bool
    {
        $existingCountries = ShippingZone::query()
            ->where('store_id', $store->getKey())
            ->when($excludeZoneId, fn ($query) => $query->where('id', '!=', $excludeZoneId))
            ->get(['countries'])
            ->flatMap(static fn (ShippingZone $zone): array => $zone->countries ?? [])
            ->map(static fn (string $code): string => strtoupper($code));

        return $existingCountries->intersect($countries)->isNotEmpty();
    }

    /** @param array{name: string, countries_json: list<string>, regions_json?: list<string>} $attributes */
    public function createShippingZone(Store $store, array $attributes): ShippingZone
    {
        return ShippingZone::query()->create([
            'store_id' => $store->getKey(),
            'name' => $attributes['name'],
            'countries' => $attributes['countries_json'],
            'regions' => $attributes['regions_json'] ?? [],
        ]);
    }

    /** @param array{name: string, countries_json: list<string>, regions_json?: list<string>} $attributes */
    public function updateShippingZone(ShippingZone $zone, array $attributes): ShippingZone
    {
        $zone->update([
            'name' => $attributes['name'],
            'countries' => $attributes['countries_json'],
            'regions' => $attributes['regions_json'] ?? [],
        ]);

        return $zone->refresh();
    }

    /** @param array{name: string, type: string, config_json: array<string, mixed>, is_active?: bool} $attributes */
    public function createShippingRate(ShippingZone $zone, array $attributes): ShippingRate
    {
        $config = $attributes['config_json'];
        $priceAmount = $config['price_amount']
            ?? data_get($config, 'tiers.0.price_amount')
            ?? 0;

        return $zone->rates()->create([
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'config_json' => $config,
            'price_amount' => $priceAmount,
            'is_active' => $attributes['is_active'] ?? true,
        ]);
    }

    /** @return array<string, mixed> */
    public function shippingZoneResource(ShippingZone $zone): array
    {
        return [
            'id' => $zone->getKey(),
            'store_id' => $zone->store_id,
            'name' => $zone->name,
            'countries_json' => $zone->countries ?? [],
            'regions_json' => $zone->regions ?? [],
            'rates' => $zone->rates->map(fn (ShippingRate $rate): array => [
                'id' => $rate->getKey(),
                'name' => $rate->name,
                'type' => $rate->type,
                'config_json' => $rate->config_json ?? [],
                'is_active' => $rate->is_active,
            ])->all(),
        ];
    }

    /** @return array{id: int, store_id: int, name: string, version: string, status: string, published_at: ?string, created_at: ?string} */
    public function installTheme(Store $store, UploadedFile $archive, ?string $name): array
    {
        if ($archive->getSize() > self::MAX_THEME_ARCHIVE_BYTES) {
            throw ValidationException::withMessages(['file' => 'The theme archive may not exceed 50 MB.']);
        }

        $zip = new ZipArchive;
        $opened = $zip->open($archive->getRealPath());

        if ($opened !== true) {
            throw ValidationException::withMessages(['file' => 'The uploaded theme archive is invalid.']);
        }

        try {
            [$manifest, $files] = $this->readThemeArchive($zip);
        } finally {
            $zip->close();
        }

        $theme = DB::transaction(function () use ($store, $manifest, $files, $name): Theme {
            $theme = Theme::query()->create([
                'store_id' => $store->getKey(),
                'name' => $name ?: $manifest['name'],
                'version' => $manifest['version'],
                'status' => 'draft',
                'is_active' => false,
            ]);
            $now = now();
            $rows = [];

            foreach ($files as $path => $content) {
                $rows[] = [
                    'theme_id' => $theme->getKey(),
                    'path' => $path,
                    'content' => $content,
                    'storage_key' => 'themes/'.$theme->getKey().'/'.$path,
                    'sha256' => hash('sha256', $content),
                    'byte_size' => strlen($content),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('theme_files')->insert($rows);

            return $theme;
        });

        return [
            'id' => (int) $theme->getKey(),
            'store_id' => (int) $theme->store_id,
            'name' => $theme->name,
            'version' => $theme->version,
            'status' => $theme->status,
            'published_at' => null,
            'created_at' => $theme->created_at?->toISOString(),
        ];
    }

    /** @return array{id: int, status: string, published_at: string} */
    public function publishTheme(Theme $theme): array
    {
        $manifest = $this->themeManifest($theme);

        if (isset($manifest['settings_schema'])) {
            $this->validateThemeSettingsSchema($manifest['settings_schema']);
        }
        $publishedAt = now();

        DB::transaction(function () use ($theme, $publishedAt): void {
            Theme::query()
                ->where('store_id', $theme->store_id)
                ->where('id', '!=', $theme->getKey())
                ->where('status', 'published')
                ->update(['status' => 'draft', 'is_active' => false]);

            $theme->update(['status' => 'published', 'is_active' => true, 'published_at' => $publishedAt]);
        });

        return [
            'id' => (int) $theme->getKey(),
            'status' => 'published',
            'published_at' => $publishedAt->toISOString(),
        ];
    }

    /** @param array<string, mixed> $settings
     * @return array{id: int, settings_json: array<string, mixed>}
     */
    public function updateThemeSettings(Theme $theme, array $settings): array
    {
        $manifest = $this->themeManifest($theme);
        $schema = $manifest['settings_schema'] ?? null;

        if ($schema !== null) {
            $this->assertSchemaValue($settings, $schema, 'settings_json');
        }

        $theme->settings()->updateOrCreate(
            ['theme_id' => $theme->getKey()],
            ['settings_json' => $settings, 'updated_at' => now()],
        );

        return ['id' => (int) $theme->getKey(), 'settings_json' => $settings];
    }

    /** @return array{0: array{name: string, version: string, templates: list<string>, settings_schema?: array<string, mixed>}, 1: array<string, string>} */
    private function readThemeArchive(ZipArchive $zip): array
    {
        if ($zip->numFiles < 2 || $zip->numFiles > self::MAX_THEME_FILES) {
            throw ValidationException::withMessages(['file' => 'The theme archive must contain between 2 and 500 files.']);
        }

        $files = [];
        $totalBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);
            $path = $entry['name'] ?? '';

            if (! is_string($path) || str_ends_with($path, '/')) {
                continue;
            }

            if ($this->isUnsafeThemePath($path)) {
                throw ValidationException::withMessages(['file' => 'The theme archive contains an unsafe file path.']);
            }

            if (strlen($path) > 255) {
                throw ValidationException::withMessages(['file' => 'Theme file paths may not exceed 255 characters.']);
            }

            if (isset($entry['size']) && $entry['size'] > self::MAX_THEME_ARCHIVE_BYTES) {
                throw ValidationException::withMessages(['file' => 'A theme file exceeds the maximum archive size.']);
            }

            $totalBytes += (int) ($entry['size'] ?? 0);

            if ($totalBytes > self::MAX_THEME_ARCHIVE_BYTES) {
                throw ValidationException::withMessages(['file' => 'The expanded theme archive may not exceed 50 MB.']);
            }

            $content = $zip->getFromIndex($index);

            if (! is_string($content)) {
                throw ValidationException::withMessages(['file' => 'A theme file could not be read.']);
            }

            $files[$path] = $content;
        }

        if (! isset($files['theme.json'])) {
            throw ValidationException::withMessages(['file' => 'The theme archive is missing its root theme.json manifest.']);
        }

        try {
            $manifest = json_decode($files['theme.json'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['file' => 'The theme manifest must contain valid JSON.']);
        }

        if (! is_array($manifest)
            || ! is_string($manifest['name'] ?? null)
            || trim($manifest['name']) === ''
            || mb_strlen($manifest['name']) > 255
            || ! is_string($manifest['version'] ?? null)
            || mb_strlen($manifest['version']) > 255
            || ! preg_match('/\A(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?\z/', $manifest['version'])
            || ! is_array($manifest['templates'] ?? null)
            || ! array_is_list($manifest['templates'])
            || $manifest['templates'] === []) {
            throw ValidationException::withMessages(['file' => 'The theme manifest requires a name, semantic version, and non-empty templates list.']);
        }

        foreach ($manifest['templates'] as $templatePath) {
            if (! is_string($templatePath)
                || ! str_starts_with($templatePath, 'templates/')
                || ! isset($files[$templatePath])
                || trim($files[$templatePath]) === '') {
                throw ValidationException::withMessages(['file' => 'The theme archive is missing a required template declared in theme.json.']);
            }
        }

        if (array_key_exists('settings_schema', $manifest)) {
            $this->validateThemeSettingsSchema($manifest['settings_schema']);
        }

        return [$manifest, $files];
    }

    /** @return array<string, mixed> */
    private function themeManifest(Theme $theme): array
    {
        $content = DB::table('theme_files')->where('theme_id', $theme->getKey())->where('path', 'theme.json')->value('content');

        if (! is_string($content)) {
            throw ValidationException::withMessages(['theme' => 'The theme manifest is missing.']);
        }

        try {
            $manifest = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['theme' => 'The theme manifest is invalid.']);
        }

        if (! is_array($manifest)) {
            throw ValidationException::withMessages(['theme' => 'The theme manifest is invalid.']);
        }

        return $manifest;
    }

    private function validateThemeSettingsSchema(mixed $schema): void
    {
        if (! is_array($schema) || ($schema['type'] ?? null) !== 'object') {
            throw ValidationException::withMessages(['file' => 'The theme settings_schema must be a JSON Schema object.']);
        }

        $this->validateSchemaDefinition($schema);
    }

    /** @param array<string, mixed> $schema */
    private function validateSchemaDefinition(array $schema): void
    {
        $types = ['object', 'array', 'string', 'integer', 'number', 'boolean', 'null'];

        if (! in_array($schema['type'] ?? null, $types, true)) {
            throw ValidationException::withMessages(['file' => 'The theme settings_schema contains an unsupported type.']);
        }

        if (isset($schema['enum']) && (! is_array($schema['enum']) || $schema['enum'] === [])) {
            throw ValidationException::withMessages(['file' => 'The theme settings_schema enum must be a non-empty array.']);
        }

        if (isset($schema['pattern'])) {
            $pattern = $schema['pattern'];

            if (! is_string($pattern) || @preg_match('~'.str_replace('~', '\\~', $pattern).'~u', '') === false) {
                throw ValidationException::withMessages(['file' => 'The theme settings_schema contains an invalid pattern.']);
            }
        }

        if (isset($schema['required']) && (! is_array($schema['required']) || ! array_is_list($schema['required']) || collect($schema['required'])->contains(fn (mixed $key): bool => ! is_string($key)))) {
            throw ValidationException::withMessages(['file' => 'The theme settings_schema required list is invalid.']);
        }

        if (isset($schema['properties'])) {
            if (! is_array($schema['properties']) || array_is_list($schema['properties']) && $schema['properties'] !== []) {
                throw ValidationException::withMessages(['file' => 'The theme settings_schema properties must be an object.']);
            }

            foreach ($schema['properties'] as $propertySchema) {
                if (! is_array($propertySchema)) {
                    throw ValidationException::withMessages(['file' => 'The theme settings_schema property definition is invalid.']);
                }

                $this->validateSchemaDefinition($propertySchema);
            }
        }

        if (isset($schema['items'])) {
            if (! is_array($schema['items'])) {
                throw ValidationException::withMessages(['file' => 'The theme settings_schema array item definition is invalid.']);
            }

            $this->validateSchemaDefinition($schema['items']);
        }

        $additionalProperties = $schema['additionalProperties'] ?? true;

        if (! is_bool($additionalProperties) && ! is_array($additionalProperties)) {
            throw ValidationException::withMessages(['file' => 'The theme settings_schema additionalProperties setting is invalid.']);
        }

        if (is_array($additionalProperties)) {
            $this->validateSchemaDefinition($additionalProperties);
        }
    }

    /** @param array<string, mixed> $schema */
    private function assertSchemaValue(mixed $value, array $schema, string $path): void
    {
        $type = $schema['type'] ?? null;
        $validType = match ($type) {
            'object' => is_array($value) && ($value === [] || ! array_is_list($value)),
            'array' => is_array($value) && array_is_list($value),
            'string' => is_string($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'null' => $value === null,
            default => false,
        };

        if (! $validType) {
            throw ValidationException::withMessages([$path => "The {$path} value does not match its theme settings schema."]);
        }

        if (array_key_exists('enum', $schema) && ! in_array($value, $schema['enum'], true)) {
            throw ValidationException::withMessages([$path => "The {$path} value is not an allowed theme setting."]);
        }

        if (is_string($value)) {
            if (isset($schema['minLength']) && mb_strlen($value) < $schema['minLength']) {
                throw ValidationException::withMessages([$path => "The {$path} value is shorter than the theme schema allows."]);
            }

            if (isset($schema['maxLength']) && mb_strlen($value) > $schema['maxLength']) {
                throw ValidationException::withMessages([$path => "The {$path} value is longer than the theme schema allows."]);
            }

            $pattern = $schema['pattern'] ?? null;

            if (isset($pattern) && (! is_string($pattern) || @preg_match('~'.str_replace('~', '\\~', $pattern).'~u', '') === false || @preg_match('~'.str_replace('~', '\\~', $pattern).'~u', $value) !== 1)) {
                throw ValidationException::withMessages([$path => "The {$path} value does not match the theme schema pattern."]);
            }
        }

        if ((is_int($value) || is_float($value)) && isset($schema['minimum']) && $value < $schema['minimum']) {
            throw ValidationException::withMessages([$path => "The {$path} value is below the theme schema minimum."]);
        }

        if ((is_int($value) || is_float($value)) && isset($schema['maximum']) && $value > $schema['maximum']) {
            throw ValidationException::withMessages([$path => "The {$path} value is above the theme schema maximum."]);
        }

        if (is_array($value) && array_is_list($value)) {
            if (isset($schema['minItems']) && count($value) < $schema['minItems']) {
                throw ValidationException::withMessages([$path => "The {$path} array has too few items."]);
            }

            if (isset($schema['maxItems']) && count($value) > $schema['maxItems']) {
                throw ValidationException::withMessages([$path => "The {$path} array has too many items."]);
            }

            if (isset($schema['items'])) {
                foreach ($value as $index => $item) {
                    $this->assertSchemaValue($item, $schema['items'], "{$path}.{$index}");
                }
            }
        }

        if ($type !== 'object') {
            return;
        }

        $properties = $schema['properties'] ?? [];
        $required = $schema['required'] ?? [];

        if (! is_array($properties) || ! is_array($required)) {
            throw ValidationException::withMessages(['theme' => 'The theme settings schema is invalid.']);
        }

        foreach ($required as $key) {
            if (! is_string($key) || ! array_key_exists($key, $value)) {
                throw ValidationException::withMessages(["{$path}.{$key}" => 'This theme setting is required.']);
            }
        }

        foreach ($value as $key => $setting) {
            if (! array_key_exists($key, $properties)) {
                if (($schema['additionalProperties'] ?? true) === false) {
                    throw ValidationException::withMessages(["{$path}.{$key}" => 'This theme setting is not defined by the theme schema.']);
                }

                if (is_array($schema['additionalProperties'] ?? null)) {
                    $this->assertSchemaValue($setting, $schema['additionalProperties'], "{$path}.{$key}");
                }

                continue;
            }

            if (! is_array($properties[$key])) {
                throw ValidationException::withMessages(['theme' => 'The theme settings schema is invalid.']);
            }

            $this->assertSchemaValue($setting, $properties[$key], "{$path}.{$key}");
        }
    }

    private function isUnsafeThemePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_contains($path, "\0")
            || str_contains($path, '\\')
            || preg_match('/\A[A-Za-z]:/', $path) === 1
            || in_array('..', explode('/', $path), true)
            || str_starts_with($path, './');
    }
}
