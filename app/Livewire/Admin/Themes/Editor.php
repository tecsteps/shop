<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\StoreDomainType;
use App\Models\Theme;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class Editor extends Component
{
    public Theme $theme;

    public string $selectedSection = 'hero';

    /** @var array<string, mixed> */
    public array $settings = [];

    public string $featuredCollectionHandles = '';

    /**
     * Sections shown in the left panel, in display order. Sections with an
     * `enabled` key support the visibility toggle.
     *
     * @var array<string, string>
     */
    private const SECTION_LABELS = [
        'announcement' => 'Announcement',
        'colors' => 'Colors',
        'hero' => 'Hero',
        'featured_collections' => 'Featured collections',
        'featured_products' => 'Featured products',
        'newsletter' => 'Newsletter',
        'rich_text' => 'Rich text',
        'footer' => 'Footer',
        'seo' => 'SEO',
    ];

    public function mount(Theme $theme): void
    {
        $this->authorize('update', $theme);

        $this->theme = $theme;
        $this->settings = array_replace_recursive(
            ThemeSettingsService::DEFAULTS,
            $theme->settings?->settings_json ?? [],
        );
        $this->featuredCollectionHandles = implode(', ', $this->settings['featured_collections']['collection_handles'] ?? []);
    }

    /**
     * Select a section to edit its settings (spec 03 §12.2).
     */
    public function selectSection(string $sectionKey): void
    {
        if (array_key_exists($sectionKey, self::SECTION_LABELS)) {
            $this->selectedSection = $sectionKey;
        }
    }

    /**
     * Toggle a section's visibility on the storefront.
     */
    public function toggleSection(string $sectionKey): void
    {
        if (isset($this->settings[$sectionKey]) && array_key_exists('enabled', $this->settings[$sectionKey])) {
            $this->settings[$sectionKey]['enabled'] = ! $this->settings[$sectionKey]['enabled'];
        }
    }

    /**
     * Save all section settings to theme_settings. The cached storefront
     * settings are invalidated by the model hooks (spec 03 §12.2).
     */
    public function save(): void
    {
        $this->authorize('update', $this->theme);

        $this->settings['featured_collections']['collection_handles'] = collect(explode(',', $this->featuredCollectionHandles))
            ->map(fn (string $handle): string => Str::slug(trim($handle)))
            ->filter()
            ->values()
            ->all();

        $this->theme->settings()->updateOrCreate(
            ['theme_id' => $this->theme->id],
            ['settings_json' => $this->settings],
        );

        $this->dispatch('toast', type: 'success', message: 'Settings saved');
    }

    /**
     * Save settings and publish the theme in one go (spec 03 §12.2).
     */
    public function saveAndPublish(): void
    {
        $this->authorize('publish', $this->theme);

        $this->save();
        $this->theme->publish();

        $this->dispatch('toast', type: 'success', message: 'Theme published');
    }

    public function render(): View
    {
        return view('livewire.admin.themes.editor', [
            'sectionLabels' => self::SECTION_LABELS,
            'previewUrl' => $this->previewUrl(),
        ])->layout('admin.layouts.app')->title('Customize '.$this->theme->name);
    }

    /**
     * Storefront home URL for the live preview iframe. Simplified: always
     * points at the live storefront; a draft-theme preview token is out of
     * scope (spec 03 §12.2 note).
     */
    private function previewUrl(): string
    {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';

        $hostname = $this->theme->store->domains()
            ->where('type', StoreDomainType::Storefront)
            ->orderByDesc('is_primary')
            ->value('hostname');

        return $hostname !== null ? "{$scheme}://{$hostname}" : (string) config('app.url');
    }
}
