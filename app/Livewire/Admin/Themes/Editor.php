<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Theme;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Editor extends Component
{
    use UsesAdminStore;

    public Theme $theme;

    /**
     * @var array<string, mixed>
     */
    public array $settings = [];

    /**
     * @var list<array{key: string, enabled: bool}>
     */
    public array $homeSections = [];

    public string $selectedSection = 'hero';

    public int $previewVersion = 0;

    public function mount(Theme $theme): void
    {
        $this->theme = $theme->load('settings');
        $this->settings = app(ThemeSettingsService::class)->mergeWithDefaults(
            $this->currentStore(),
            $this->theme->settings?->settings_json ?? [],
        );
        $this->homeSections = app(ThemeSettingsService::class)->homeSections($this->settings);
        $this->selectedSection = $this->homeSections[0]['key'] ?? 'hero';
    }

    public function selectSection(string $sectionKey): void
    {
        if (! $this->editorSectionDefinitions()->has($sectionKey)) {
            return;
        }

        $this->selectedSection = $sectionKey;
    }

    public function moveSectionUp(string $sectionKey): void
    {
        $this->moveSection($sectionKey, -1);
    }

    public function moveSectionDown(string $sectionKey): void
    {
        $this->moveSection($sectionKey, 1);
    }

    public function updated(string $property, mixed $value = null): void
    {
        if (str_starts_with($property, 'homeSections.')) {
            $this->syncHomeSections();
        }
    }

    public function save(): void
    {
        $this->persistSettings();
        $this->refreshPreview();

        $this->notify('Theme settings saved.');
    }

    public function publish(): void
    {
        $this->persistSettings();
        $this->refreshPreview();

        $store = $this->currentStore();

        Theme::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->update([
                'status' => ThemeStatus::Draft->value,
                'published_at' => null,
            ]);

        $this->theme->forceFill([
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ])->save();

        Cache::forget("theme_settings:{$store->id}");

        $this->notify('Theme saved and published.');
    }

    public function refreshPreview(): void
    {
        $this->previewVersion++;
    }

    public function render(): View
    {
        return view('livewire.admin.themes.editor', [
            'previewUrl' => route('home', ['preview' => $this->previewVersion]),
            'sectionDefinitions' => $this->editorSectionDefinitions()->all(),
            'selectedSectionDefinition' => $this->editorSectionDefinitions()->get($this->selectedSection),
        ])->layout('livewire.admin.layout.app', [
            'title' => 'Theme editor',
        ]);
    }

    private function persistSettings(): void
    {
        $this->syncHomeSections();

        $this->validate($this->rules());

        $store = $this->currentStore();
        $settings = app(ThemeSettingsService::class)->prepareForStorage($store, $this->settings);

        $this->theme->settings()->updateOrCreate(
            ['theme_id' => $this->theme->id],
            ['settings_json' => $settings],
        );

        Cache::forget("theme_settings:{$store->id}");

        $this->settings = $settings;
        $this->homeSections = app(ThemeSettingsService::class)->homeSections($settings);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        $sectionKeys = $this->homeSectionDefinitions()->keys()->all();

        return [
            'homeSections' => ['required', 'array'],
            'homeSections.*.key' => ['required', Rule::in($sectionKeys)],
            'homeSections.*.enabled' => ['boolean'],
            'settings.announcement.enabled' => ['boolean'],
            'settings.announcement.text' => ['nullable', 'string', 'max:160'],
            'settings.announcement.link' => ['nullable', 'string', 'max:255'],
            'settings.home.hero_heading' => ['required', 'string', 'max:120'],
            'settings.home.hero_subheading' => ['nullable', 'string', 'max:240'],
            'settings.home.hero_cta_label' => ['required', 'string', 'max:80'],
            'settings.home.hero_cta_url' => ['required', 'string', 'max:255'],
            'settings.home.featured_collections_heading' => ['required', 'string', 'max:120'],
            'settings.home.featured_collections_subheading' => ['nullable', 'string', 'max:240'],
            'settings.home.featured_collections_count' => ['required', 'integer', 'min:2', 'max:4'],
            'settings.home.featured_products_heading' => ['required', 'string', 'max:120'],
            'settings.home.featured_products_count' => ['required', 'integer', 'min:4', 'max:8'],
            'settings.home.newsletter_heading' => ['required', 'string', 'max:120'],
            'settings.home.newsletter_subheading' => ['nullable', 'string', 'max:240'],
            'settings.home.rich_text_heading' => ['required', 'string', 'max:120'],
            'settings.home.rich_text_html' => ['nullable', 'string', 'max:65535'],
            'settings.footer.contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    private function moveSection(string $sectionKey, int $direction): void
    {
        $index = collect($this->homeSections)->search(
            fn (array $section): bool => $section['key'] === $sectionKey,
        );

        if ($index === false) {
            return;
        }

        $target = $index + $direction;

        if (! array_key_exists($target, $this->homeSections)) {
            return;
        }

        $currentSection = $this->homeSections[$index];
        $this->homeSections[$index] = $this->homeSections[$target];
        $this->homeSections[$target] = $currentSection;

        $this->syncHomeSections();
    }

    private function syncHomeSections(): void
    {
        $this->settings['home']['sections'] = app(ThemeSettingsService::class)->homeSections([
            'home' => [
                'sections' => $this->homeSections,
            ],
        ]);
        $this->homeSections = $this->settings['home']['sections'];
    }

    /**
     * @return \Illuminate\Support\Collection<string, array{key: string, label: string, description: string}>
     */
    private function homeSectionDefinitions(): \Illuminate\Support\Collection
    {
        return collect(app(ThemeSettingsService::class)->homeSectionDefinitions())->keyBy('key');
    }

    /**
     * @return \Illuminate\Support\Collection<string, array{key: string, label: string, description: string}>
     */
    private function editorSectionDefinitions(): \Illuminate\Support\Collection
    {
        return collect([
            [
                'key' => 'announcement',
                'label' => 'Announcement',
                'description' => 'Top storefront bar.',
            ],
            ...app(ThemeSettingsService::class)->homeSectionDefinitions(),
            [
                'key' => 'footer',
                'label' => 'Footer',
                'description' => 'Store footer.',
            ],
        ])->keyBy('key');
    }
}
