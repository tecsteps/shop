<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Editor extends Component
{
    public Theme $theme;

    public array $sections = [];

    public ?string $selectedSection = null;

    public array $sectionSettings = [];

    public array $settings = [];

    public string $previewUrl = '';

    public int $previewVersion = 0;

    public function mount(Theme $theme): void
    {
        Gate::authorize('manage-themes');

        $this->theme = $this->findTheme($theme->getKey());
        $this->settings = $this->theme->settings?->settings_json ?? [];
        $this->sections = $this->sectionsForTheme($this->theme);
        $this->selectedSection = $this->sections[0]['key'] ?? null;
        $this->loadSelectedSectionSettings();
        $this->refreshPreview();
    }

    public function selectSection(string $sectionKey): void
    {
        Gate::authorize('manage-themes');
        $theme = $this->currentTheme();
        $sections = $this->sectionsForTheme($theme);
        $section = collect($sections)->firstWhere('key', $sectionKey);

        abort_unless($section !== null, 404);

        $this->saveSelectedSectionToSettings($sections);
        $this->sections = $sections;
        $this->selectedSection = $sectionKey;
        $this->loadSelectedSectionSettings();
    }

    public function updateSetting(string $key, mixed $value): void
    {
        Gate::authorize('manage-themes');
        $sections = $this->sectionsForTheme($this->currentTheme());
        $field = data_get(collect($sections)->firstWhere('key', $this->selectedSection), 'fields', []);
        $field = collect($field)->firstWhere('key', $key);

        abort_unless($field !== null, 404);

        $value = $this->validatedSettingValue($field, $value);
        $this->sectionSettings[$key] = $value;
        data_set($this->settings, $field['path'], $value);
    }

    public function save(): void
    {
        Gate::authorize('manage-themes');

        $theme = $this->currentTheme();
        $sections = $this->sectionsForTheme($theme);
        $this->saveSelectedSectionToSettings($sections);
        $settings = $theme->settings?->settings_json ?? [];

        foreach ($sections as $section) {
            foreach ($section['fields'] as $field) {
                $value = data_get($this->settings, $field['path'], $field['default']);
                $value = $this->validatedSettingValue($field, $value);
                data_set($settings, $field['path'], $value);
                data_set($this->settings, $field['path'], $value);
            }
        }

        $theme->settings()->updateOrCreate(
            ['theme_id' => $theme->getKey()],
            ['settings_json' => $settings, 'updated_at' => now()],
        );

        $this->theme = $theme->fresh(['settings']);
        session()->flash('status', 'Theme settings saved.');
    }

    public function publish(): void
    {
        Gate::authorize('manage-themes');

        $theme = $this->currentTheme();
        $storeId = $this->currentStore()->getKey();
        $publishedAt = now();

        DB::transaction(function () use ($theme, $storeId, $publishedAt): void {
            Theme::query()
                ->where('store_id', $storeId)
                ->where('id', '!=', $theme->getKey())
                ->update(['status' => 'draft', 'is_active' => false]);

            $theme->update([
                'status' => 'published',
                'is_active' => true,
                'published_at' => $publishedAt,
            ]);
        });

        $this->theme = $theme->fresh(['settings']);
        session()->flash('status', "{$theme->name} is now the published theme.");
    }

    public function saveAndPublish(): void
    {
        $this->save();
        $this->publish();
    }

    public function refreshPreview(): void
    {
        Gate::authorize('manage-themes');
        $this->previewVersion = now()->getTimestamp();
    }

    public function render(): mixed
    {
        Gate::authorize('manage-themes');

        $this->theme = $this->currentTheme();
        $this->sections = $this->sectionsForTheme($this->theme);

        if ($this->selectedSection === null || ! collect($this->sections)->contains('key', $this->selectedSection)) {
            $this->selectedSection = $this->sections[0]['key'] ?? null;
            $this->loadSelectedSectionSettings();
        }

        $this->previewUrl = route('home', ['_theme_preview' => $this->previewVersion]);

        return view('livewire.admin.themes.editor')
            ->layout('layouts.admin-theme-editor', ['theme' => $this->theme]);
    }

    private function sectionsForTheme(Theme $theme): array
    {
        $manifestContent = DB::table('theme_files')
            ->where('theme_id', $theme->getKey())
            ->where('path', 'theme.json')
            ->value('content');

        $manifest = is_string($manifestContent) ? json_decode($manifestContent, true) : null;
        $schema = is_array($manifest) ? ($manifest['settings_schema'] ?? null) : null;

        if (is_array($schema) && is_array($schema['properties'] ?? null)) {
            $sections = $this->sectionsFromSchema(
                $schema['properties'],
                $theme->settings?->settings_json ?? [],
                $schema['required'] ?? [],
            );

            if ($sections !== []) {
                return $sections;
            }
        }

        return $this->defaultSections();
    }

    /** @param array<string, mixed> $properties
     * @param  array<string, mixed>  $settings
     * @return list<array{key: string, label: string, fields: list<array<string, mixed>>}>
     */
    private function sectionsFromSchema(array $properties, array $settings, mixed $rootRequired): array
    {
        $sections = [];
        $rootRequired = is_array($rootRequired) ? $rootRequired : [];
        $sectionProperties = $properties;
        unset($sectionProperties['required']);

        foreach ($sectionProperties as $sectionKey => $definition) {
            if (! $this->isSafeKey($sectionKey) || ! is_array($definition)) {
                continue;
            }

            $nested = $definition['properties'] ?? null;
            if (($definition['type'] ?? null) === 'object' && is_array($nested)) {
                $fields = $this->fieldsFromProperties($nested, (string) $sectionKey, $settings, $definition['required'] ?? []);
                $label = (string) ($definition['title'] ?? str($sectionKey)->headline());
            } else {
                $fields = $this->fieldsFromProperties([$sectionKey => $definition], '', $settings, $rootRequired);
                $label = 'General';
            }

            if ($fields !== []) {
                $key = ($definition['type'] ?? null) === 'object' && is_array($nested)
                    ? (string) $sectionKey
                    : 'general';

                $sections[$key] ??= ['key' => $key, 'label' => $label, 'fields' => []];
                array_push($sections[$key]['fields'], ...$fields);
            }
        }

        return array_values($sections);
    }

    /** @param array<string, mixed> $properties
     * @param  array<string, mixed>  $settings
     * @return list<array<string, mixed>>
     */
    private function fieldsFromProperties(array $properties, string $prefix, array $settings, mixed $requiredProperties): array
    {
        $requiredProperties = is_array($requiredProperties) ? $requiredProperties : [];
        $fields = [];

        foreach ($properties as $property => $definition) {
            if (! $this->isSafeKey($property) || ! is_array($definition)) {
                continue;
            }

            $path = $prefix === '' ? (string) $property : $prefix.'.'.$property;
            $field = $this->fieldFromDefinition($property, $path, $definition, in_array($property, $requiredProperties, true));

            if ($field === null) {
                continue;
            }

            $field['default'] = data_get($settings, $path, $field['default']);
            $fields[] = $field;
        }

        return $fields;
    }

    /** @param array<string, mixed> $definition
     * @return array<string, mixed>|null
     */
    private function fieldFromDefinition(string $key, string $path, array $definition, bool $required): ?array
    {
        $schemaType = $definition['type'] ?? null;
        $enum = $definition['enum'] ?? null;
        $inputType = $definition['x-ui-type'] ?? $definition['ui'] ?? null;

        if (is_array($enum) && $enum !== []) {
            $type = 'select';
        } elseif ($inputType === 'textarea' || ($schemaType === 'string' && ($definition['format'] ?? null) === 'textarea')) {
            $type = 'textarea';
        } elseif ($inputType === 'color' || ($schemaType === 'string' && ($definition['format'] ?? null) === 'color')) {
            $type = 'color';
        } else {
            $type = match ($schemaType) {
                'string' => 'text',
                'integer', 'number' => 'number',
                'boolean' => 'checkbox',
                default => null,
            };
        }

        if ($type === null) {
            return null;
        }

        return [
            'key' => $this->settingKey($path),
            'path' => $path,
            'label' => (string) ($definition['title'] ?? str($key)->headline()),
            'type' => $type,
            'options' => $enum ?? [],
            'required' => $required,
            'default' => $definition['default'] ?? $this->defaultValueFor($type, $enum ?? []),
        ];
    }

    /** @return list<array{key: string, label: string, fields: list<array<string, mixed>>}> */
    private function defaultSections(): array
    {
        return [
            [
                'key' => 'header',
                'label' => 'Header',
                'fields' => [
                    $this->field('logo_url', 'Logo URL', 'text', null),
                    $this->field('sticky_header', 'Keep header visible while scrolling', 'checkbox', true),
                    $this->field('announcement.enabled', 'Show announcement bar', 'checkbox', false),
                    $this->field('announcement.text', 'Announcement text', 'text', ''),
                    $this->field('announcement.url', 'Announcement link', 'text', ''),
                ],
            ],
            [
                'key' => 'hero',
                'label' => 'Hero',
                'fields' => [
                    $this->field('home.hero.enabled', 'Show hero section', 'checkbox', true),
                    $this->field('home.hero.heading', 'Heading', 'text', 'Welcome'),
                    $this->field('home.hero.subheading', 'Subheading', 'textarea', ''),
                    $this->field('home.hero.cta_text', 'Button label', 'text', 'Shop now'),
                    $this->field('home.hero.cta_url', 'Button link', 'text', '/collections'),
                ],
            ],
            [
                'key' => 'colors',
                'label' => 'Colors',
                'fields' => [
                    $this->field('colors.primary', 'Primary color', 'color', '#1a1a2e'),
                    $this->field('colors.secondary', 'Secondary color', 'color', '#e94560'),
                    $this->field('colors.accent', 'Accent color', 'color', '#e94560'),
                ],
            ],
            [
                'key' => 'products',
                'label' => 'Products',
                'fields' => [
                    $this->field('products_per_page', 'Products per page', 'number', 12),
                    $this->field('show_vendor', 'Show product vendor', 'checkbox', true),
                    $this->field('show_quantity_selector', 'Show quantity selector', 'checkbox', true),
                ],
            ],
            [
                'key' => 'footer',
                'label' => 'Footer',
                'fields' => [
                    $this->field('footer_text', 'Footer text', 'text', ''),
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function field(string $path, string $label, string $type, mixed $default): array
    {
        return [
            'key' => $this->settingKey($path),
            'path' => $path,
            'label' => $label,
            'type' => $type,
            'options' => [],
            'required' => false,
            'default' => $default,
        ];
    }

    private function defaultValueFor(string $type, array $options): mixed
    {
        return match ($type) {
            'checkbox' => false,
            'number' => 0,
            'select' => $options[0] ?? '',
            'color' => '#000000',
            default => '',
        };
    }

    private function isSafeKey(int|string $key): bool
    {
        return preg_match('/\A[a-zA-Z0-9_-]+\z/', (string) $key) === 1;
    }

    private function settingKey(string $path): string
    {
        return 'setting_'.trim((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $path), '_');
    }

    private function loadSelectedSectionSettings(): void
    {
        $section = collect($this->sections)->firstWhere('key', $this->selectedSection);
        $this->sectionSettings = [];

        foreach ($section['fields'] ?? [] as $field) {
            $this->sectionSettings[$field['key']] = data_get($this->settings, $field['path'], $field['default']);
        }
    }

    private function saveSelectedSectionToSettings(array $sections): void
    {
        $section = collect($sections)->firstWhere('key', $this->selectedSection);

        foreach ($section['fields'] ?? [] as $field) {
            if (array_key_exists($field['key'], $this->sectionSettings)) {
                data_set($this->settings, $field['path'], $this->sectionSettings[$field['key']]);
            }
        }
    }

    private function validatedSettingValue(array $field, mixed $value): mixed
    {
        $rules = match ($field['type']) {
            'checkbox' => ['boolean'],
            'number' => ['numeric'],
            'color' => ['string', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
            'select' => ['string', Rule::in($field['options'])],
            default => ['string', 'max:10000'],
        };

        if ($field['required'] && $field['type'] !== 'checkbox') {
            array_unshift($rules, 'required');
        }

        $validator = Validator::make(['value' => $value], ['value' => $rules]);

        if ($validator->fails()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'settings.'.$field['path'] => $validator->errors()->first('value'),
            ]);
        }

        $validated = $validator->validated()['value'];

        if ($field['type'] === 'number' && is_string($validated) && is_numeric($validated)) {
            return str_contains($validated, '.') ? (float) $validated : (int) $validated;
        }

        return $validated;
    }

    private function currentTheme(): Theme
    {
        return $this->findTheme($this->theme->getKey());
    }

    private function findTheme(int|string $themeId): Theme
    {
        Gate::authorize('manage-themes');

        return Theme::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->with('settings')
            ->findOrFail($themeId);
    }

    private function currentStore(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }
}
