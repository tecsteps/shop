<?php

namespace App\Livewire\Admin\Themes;

use App\Livewire\Admin\AdminComponent;
use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Support\SafeUrl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class Editor extends AdminComponent
{
    public Theme $theme;

    /** @var array<string, array{label: string, fields: array<string, array<string, mixed>>}> */
    public array $sections = [];

    public ?string $selectedSection = null;

    /** @var array<string, mixed> */
    public array $sectionSettings = [];

    /** @var array<string, mixed> */
    public array $allSettings = [];

    public string $previewUrl = '/';

    public function mount(Theme $theme): void
    {
        $this->authorizeThemes();
        abort_unless((int) $theme->store_id === (int) $this->currentStore()->id, 404);
        $this->authorizeAction('view', $theme);
        $this->theme = $theme->load('settings');
        session()->forget($this->previewSessionKey());
        $this->sections = $this->schema();
        $this->allSettings = array_replace_recursive($this->defaults(), (array) ($theme->settings?->settings_json ?? []));
        $this->previewUrl = url('/').'?theme_preview='.$theme->id;
        $this->selectSection((string) array_key_first($this->sections));
    }

    public function selectSection(string $sectionKey): void
    {
        $this->authorizeThemes();
        $this->authorizeAction('view', $this->theme);
        abort_unless(array_key_exists($sectionKey, $this->sections), 404);
        $this->selectedSection = $sectionKey;
        $this->sectionSettings = [];
        foreach ($this->sections[$sectionKey]['fields'] as $key => $definition) {
            $this->sectionSettings[$key] = data_get($this->allSettings, $key, $definition['default'] ?? null);
        }
    }

    public function updateSetting(string $key, mixed $value): void
    {
        $this->authorizeThemes();
        $this->authorizeAction('update', $this->theme);
        abort_unless($this->selectedSection && array_key_exists($key, $this->sections[$this->selectedSection]['fields']), 422);
        if ((str_ends_with($key, '.url') || str_ends_with($key, '_url')) && ! SafeUrl::isAllowed($value)) {
            abort(422, 'Links must be relative, HTTP, or HTTPS URLs.');
        }
        if (str_ends_with($key, '.url') || str_ends_with($key, '_url')) {
            $value = SafeUrl::normalize($value);
        }
        data_set($this->allSettings, $key, $value);
        $this->sectionSettings[$key] = $value;
        $this->refreshPreview();
    }

    public function save(): void
    {
        $this->authorizeThemes();
        $this->authorizeAction('update', $this->theme);
        $this->mergeSelectedSettings();
        ThemeSettings::query()->updateOrCreate(['theme_id' => $this->theme->id], ['settings_json' => $this->allSettings]);
        Cache::forget('theme-settings:'.$this->currentStore()->id);
        session()->forget($this->previewSessionKey());
        $this->theme->load('settings');
        $this->toast('Theme settings saved');
    }

    public function publish(): void
    {
        $this->authorizeThemes();
        $this->authorizeAction('update', $this->theme);
        $this->mergeSelectedSettings();

        DB::transaction(function (): void {
            ThemeSettings::query()->updateOrCreate(['theme_id' => $this->theme->id], ['settings_json' => $this->allSettings]);
            Theme::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->update(['status' => 'draft', 'published_at' => null]);
            $this->theme->update(['status' => 'published', 'published_at' => now()]);
        });

        Cache::forget('theme-settings:'.$this->currentStore()->id);
        session()->forget($this->previewSessionKey());
        $this->theme->refresh()->load('settings');
        $this->toast('Theme published');
    }

    public function refreshPreview(): void
    {
        $this->authorizeThemes();
        $this->authorizeAction('view', $this->theme);
        $this->allSettings = SafeUrl::sanitizeThemeSettings($this->allSettings);
        session()->put($this->previewSessionKey(), [
            'user_id' => $this->adminUser()->id,
            'settings' => $this->allSettings,
        ]);
        $this->dispatch('theme-preview-refresh', url: $this->previewUrl, settings: $this->allSettings);
    }

    public function leaveEditor(): mixed
    {
        $this->authorizeThemes();
        $this->authorizeAction('view', $this->theme);
        session()->forget($this->previewSessionKey());

        return $this->redirect('/admin/themes', navigate: true);
    }

    public function render(): View
    {
        return $this->admin(view('admin.themes.editor'), 'Customize '.$this->theme->name, [
            ['label' => 'Themes', 'url' => url('/admin/themes')],
            ['label' => $this->theme->name],
        ]);
    }

    private function authorizeThemes(): void
    {
        $this->requireRoles(['owner', 'admin']);
    }

    private function previewSessionKey(): string
    {
        return 'theme_preview.'.$this->theme->id;
    }

    private function mergeSelectedSettings(): void
    {
        if (! $this->selectedSection) {
            return;
        }
        foreach ($this->sectionSettings as $key => $value) {
            abort_unless(array_key_exists($key, $this->sections[$this->selectedSection]['fields']), 422);
            data_set($this->allSettings, $key, $value);
        }
        $this->allSettings = SafeUrl::sanitizeThemeSettings($this->allSettings);
    }

    /** @return array<string, array{label: string, fields: array<string, array<string, mixed>>}> */
    private function schema(): array
    {
        return [
            'colors' => ['label' => 'Colors', 'fields' => [
                'colors.primary' => ['label' => 'Primary color', 'type' => 'color', 'default' => '#2563eb'],
                'colors.secondary' => ['label' => 'Secondary color', 'type' => 'color', 'default' => '#0f172a'],
                'colors.accent' => ['label' => 'Accent color', 'type' => 'color', 'default' => '#f59e0b'],
                'dark_mode' => ['label' => 'Color scheme', 'type' => 'select', 'default' => 'system', 'options' => [
                    'system' => 'Follow system', 'toggle' => 'Customer toggle', 'light' => 'Always light', 'dark' => 'Always dark',
                ]],
            ]],
            'announcement' => ['label' => 'Announcement bar', 'fields' => [
                'announcement.enabled' => ['label' => 'Show announcement', 'type' => 'checkbox', 'default' => false],
                'announcement.text' => ['label' => 'Message', 'type' => 'text', 'default' => 'Free shipping on orders over €50'],
                'announcement.url' => ['label' => 'Link', 'type' => 'text', 'default' => ''],
                'announcement.background' => ['label' => 'Background', 'type' => 'color', 'default' => '#0f172a'],
            ]],
            'header' => ['label' => 'Header', 'fields' => [
                'header.sticky' => ['label' => 'Sticky header', 'type' => 'checkbox', 'default' => true],
                'header.logo_url' => ['label' => 'Logo URL', 'type' => 'text', 'default' => ''],
            ]],
            'hero' => ['label' => 'Homepage hero', 'fields' => [
                'home.hero.enabled' => ['label' => 'Show hero', 'type' => 'checkbox', 'default' => true],
                'home.hero.heading' => ['label' => 'Heading', 'type' => 'text', 'default' => 'Discover something special'],
                'home.hero.subheading' => ['label' => 'Subheading', 'type' => 'textarea', 'default' => 'Thoughtfully selected products, delivered to your door.'],
                'home.hero.cta_label' => ['label' => 'Button label', 'type' => 'text', 'default' => 'Shop now'],
                'home.hero.cta_url' => ['label' => 'Button link', 'type' => 'text', 'default' => '/collections'],
                'home.hero.image_url' => ['label' => 'Image URL', 'type' => 'text', 'default' => ''],
            ]],
            'footer' => ['label' => 'Footer', 'fields' => [
                'footer.description' => ['label' => 'Description', 'type' => 'textarea', 'default' => ''],
            ]],
        ];
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        $defaults = [];
        foreach ($this->schema() as $section) {
            foreach ($section['fields'] as $key => $definition) {
                data_set($defaults, $key, $definition['default'] ?? null);
            }
        }

        return $defaults;
    }
}
