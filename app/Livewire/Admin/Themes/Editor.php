<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\StoreDomainType;
use App\Enums\ThemeStatus;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Theme;
use App\Services\ThemeSettingsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Three-panel theme editor (spec 03 section 12.2): section list on the left,
 * live storefront preview in the center, settings fields on the right.
 */
#[Layout('layouts::admin')]
class Editor extends Component
{
    use AuthorizesRequests, SendsToasts;

    public Theme $theme;

    public string $selectedSection = 'colors';

    /**
     * Flat theme settings values keyed by setting name. The
     * featured_collection_handles list is edited as a comma-separated string.
     *
     * @var array<string, mixed>
     */
    public array $settings = [];

    /**
     * Home page section order (subset of the orderable sections).
     *
     * @var list<string>
     */
    public array $sectionOrder = [];

    /**
     * Whether each orderable home section is enabled.
     *
     * @var array<string, bool>
     */
    public array $enabledSections = [];

    public function mount(int $themeId): void
    {
        $this->theme = Theme::query()->with('settings')->findOrFail($themeId);

        $this->authorize('update', $this->theme);

        $stored = array_replace(
            ThemeSettingsService::defaults(),
            $this->theme->settings?->settings_json ?? [],
        );

        $enabled = $stored['sections'];
        $this->sectionOrder = [...$enabled, ...array_diff(array_keys($this->orderableSections()), $enabled)];
        $this->enabledSections = array_map(
            fn (string $key): bool => in_array($key, $enabled, true),
            array_combine(array_keys($this->orderableSections()), array_keys($this->orderableSections())),
        );

        unset($stored['sections']);
        $stored['featured_collection_handles'] = implode(', ', $stored['featured_collection_handles'] ?? []);

        $this->settings = $stored;
    }

    public function selectSection(string $sectionKey): void
    {
        if (array_key_exists($sectionKey, $this->sections())) {
            $this->selectedSection = $sectionKey;
        }
    }

    public function toggleSection(string $sectionKey): void
    {
        if (array_key_exists($sectionKey, $this->orderableSections())) {
            $this->enabledSections[$sectionKey] = ! ($this->enabledSections[$sectionKey] ?? false);
        }
    }

    /**
     * Drag-to-reorder handler (wire:sort) for the home page section list.
     */
    public function reorderSections(string $sectionKey, int $position): void
    {
        $currentIndex = array_search($sectionKey, $this->sectionOrder, true);

        if ($currentIndex === false) {
            return;
        }

        array_splice($this->sectionOrder, $currentIndex, 1);
        array_splice($this->sectionOrder, $position, 0, [$sectionKey]);
    }

    public function save(): void
    {
        $this->authorize('update', $this->theme);

        $this->theme->settings()->updateOrCreate(
            ['theme_id' => $this->theme->getKey()],
            ['settings_json' => $this->buildSettingsJson()],
        );

        $this->toast(__('Theme settings saved.'));
    }

    /**
     * Save and publish in one step ("Save & publish" toolbar button).
     */
    public function publish(): void
    {
        $this->authorize('publish', $this->theme);

        $this->save();

        DB::transaction(function (): void {
            Theme::query()
                ->whereKeyNot($this->theme->getKey())
                ->where('status', ThemeStatus::Published)
                ->update(['status' => ThemeStatus::Draft]);

            $this->theme->update([
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ]);
        });

        app(ThemeSettingsService::class)->forget($this->theme->store_id);

        $this->toast(__('Theme published'));
    }

    /**
     * All editor sections: fixed settings groups plus orderable home
     * sections. Field types map to spec 03 section 12.2 input kinds.
     *
     * @return array<string, array{label: string, fields: list<array{key: string, label: string, type: string, options?: array<string, string>}>}>
     */
    public function sections(): array
    {
        return [
            'header' => [
                'label' => __('Header'),
                'fields' => [
                    ['key' => 'logo_url', 'label' => __('Logo URL'), 'type' => 'text'],
                    ['key' => 'sticky_header', 'label' => __('Sticky header'), 'type' => 'checkbox'],
                    ['key' => 'show_announcement_bar', 'label' => __('Show announcement bar'), 'type' => 'checkbox'],
                    ['key' => 'announcement_text', 'label' => __('Announcement text'), 'type' => 'text'],
                    ['key' => 'announcement_link', 'label' => __('Announcement link'), 'type' => 'text'],
                ],
            ],
            'colors' => [
                'label' => __('Colors & typography'),
                'fields' => [
                    ['key' => 'primary_color', 'label' => __('Primary color'), 'type' => 'color'],
                    ['key' => 'secondary_color', 'label' => __('Secondary color'), 'type' => 'color'],
                    ['key' => 'font_family', 'label' => __('Font family'), 'type' => 'select', 'options' => [
                        'Instrument Sans, sans-serif' => 'Instrument Sans',
                        'Inter, sans-serif' => 'Inter',
                        'Georgia, serif' => 'Georgia',
                        'Menlo, monospace' => 'Menlo',
                    ]],
                    ['key' => 'dark_mode', 'label' => __('Dark mode'), 'type' => 'select', 'options' => [
                        'system' => __('Follow system'),
                        'light' => __('Light'),
                        'dark' => __('Dark'),
                    ]],
                ],
            ],
            'catalog' => [
                'label' => __('Product catalog'),
                'fields' => [
                    ['key' => 'products_per_page', 'label' => __('Products per page'), 'type' => 'number'],
                    ['key' => 'show_vendor', 'label' => __('Show vendor'), 'type' => 'checkbox'],
                    ['key' => 'show_quantity_selector', 'label' => __('Show quantity selector'), 'type' => 'checkbox'],
                ],
            ],
            'footer' => [
                'label' => __('Footer'),
                'fields' => [
                    ['key' => 'footer_text', 'label' => __('Footer text'), 'type' => 'text'],
                ],
            ],
            ...$this->orderableSections(),
        ];
    }

    /**
     * Home page sections that can be reordered and toggled (the persisted
     * "sections" list in theme settings).
     *
     * @return array<string, array{label: string, fields: list<array{key: string, label: string, type: string, options?: array<string, string>}>}>
     */
    public function orderableSections(): array
    {
        return [
            'hero' => [
                'label' => __('Hero'),
                'fields' => [
                    ['key' => 'hero_heading', 'label' => __('Heading'), 'type' => 'text'],
                    ['key' => 'hero_subheading', 'label' => __('Subheading'), 'type' => 'textarea'],
                    ['key' => 'hero_cta_text', 'label' => __('Button text'), 'type' => 'text'],
                    ['key' => 'hero_cta_link', 'label' => __('Button link'), 'type' => 'text'],
                    ['key' => 'hero_image_url', 'label' => __('Image URL'), 'type' => 'text'],
                ],
            ],
            'featured-collections' => [
                'label' => __('Featured collections'),
                'fields' => [
                    ['key' => 'featured_collection_handles', 'label' => __('Collection handles'), 'type' => 'text'],
                ],
            ],
            'featured-products' => [
                'label' => __('Featured products'),
                'fields' => [
                    ['key' => 'featured_products_count', 'label' => __('Number of products'), 'type' => 'number'],
                    ['key' => 'featured_products_collection_handle', 'label' => __('Collection handle'), 'type' => 'text'],
                ],
            ],
            'newsletter' => [
                'label' => __('Newsletter'),
                'fields' => [
                    ['key' => 'show_newsletter', 'label' => __('Show newsletter signup'), 'type' => 'checkbox'],
                ],
            ],
            'rich-text' => [
                'label' => __('Rich text'),
                'fields' => [
                    ['key' => 'rich_text_html', 'label' => __('Content (HTML)'), 'type' => 'textarea'],
                ],
            ],
        ];
    }

    /**
     * Storefront home URL for the live preview iframe, based on the store's
     * primary storefront domain.
     */
    #[Computed]
    public function previewUrl(): ?string
    {
        $hostname = $this->theme->store()
            ->withoutGlobalScopes()
            ->first()
            ?->domains()
            ->where('type', StoreDomainType::Storefront)
            ->orderByDesc('is_primary')
            ->value('hostname');

        return $hostname !== null ? request()->getScheme().'://'.$hostname : null;
    }

    public function render(): View
    {
        return view('livewire.admin.themes.editor')->title($this->theme->name);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSettingsJson(): array
    {
        $settings = $this->settings;

        $settings['featured_collection_handles'] = collect(explode(',', (string) ($settings['featured_collection_handles'] ?? '')))
            ->map(fn (string $handle): string => trim($handle))
            ->filter(fn (string $handle): bool => $handle !== '')
            ->values()
            ->all();

        foreach (['products_per_page', 'featured_products_count'] as $numericKey) {
            $settings[$numericKey] = max(1, (int) ($settings[$numericKey] ?? 1));
        }

        $settings['sections'] = array_values(array_filter(
            $this->sectionOrder,
            fn (string $key): bool => $this->enabledSections[$key] ?? false,
        ));

        return $settings;
    }
}
