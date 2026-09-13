<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Theme;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Editor extends Component
{
    use ResolvesAdminStore;

    public Theme $theme;

    public ?string $selectedSection = null;

    /** @var array<string, mixed> */
    public array $sectionSettings = [];

    /** @var array<string, mixed> */
    public array $allSettings = [];

    public function mount(Theme $theme): void
    {
        abort_if($theme->store_id !== $this->store()->getKey(), 404);

        Gate::authorize('view', $theme);

        $this->theme = $theme;
        $this->allSettings = is_array($theme->themeSetting?->settings_json)
            ? $theme->themeSetting->settings_json
            : [];
    }

    public function selectSection(string $sectionKey): void
    {
        $this->persistSection();
        $this->selectedSection = $sectionKey;
        $this->sectionSettings = $this->allSettings[$sectionKey] ?? $this->sectionDefaults($sectionKey);
    }

    public function updateSetting(string $key, mixed $value): void
    {
        $this->sectionSettings[$key] = $value;
    }

    public function save(): void
    {
        Gate::authorize('update', $this->theme);

        $this->persistSection();

        $this->theme->themeSetting()->updateOrCreate(
            ['theme_id' => $this->theme->getKey()],
            ['settings_json' => $this->allSettings]
        );

        $this->toast('success', 'Theme settings saved.');
    }

    public function publish(): void
    {
        Gate::authorize('publish', $this->theme);

        $this->save();

        Theme::query()
            ->where('store_id', $this->store()->getKey())
            ->where('status', ThemeStatus::Published)
            ->update(['status' => ThemeStatus::Draft->value]);

        $this->theme->update(['status' => ThemeStatus::Published, 'published_at' => now()]);

        $this->toast('success', 'Theme published.');
    }

    public function refreshPreview(): void
    {
        $this->dispatch('refresh-theme-preview');
    }

    public function render()
    {
        $store = $this->store();
        $domain = $store->storeDomains()->where('is_primary', true)->first()
            ?? $store->storeDomains()->first();

        return view('livewire.admin.themes.editor', [
            'sections' => $this->sections(),
            'previewUrl' => $domain !== null ? 'http://'.$domain->hostname.'/' : url('/'),
        ])->layout('livewire.admin.layout.app', ['title' => 'Customize '.$this->theme->name]);
    }

    /**
     * @return array<string, array{label: string, fields: array<int, array<string, mixed>>}>
     */
    protected function sections(): array
    {
        return [
            'announcement' => [
                'label' => 'Announcement bar',
                'fields' => [
                    ['key' => 'show', 'label' => 'Show announcement', 'type' => 'checkbox'],
                    ['key' => 'text', 'label' => 'Announcement text', 'type' => 'text'],
                    ['key' => 'link', 'label' => 'Announcement link', 'type' => 'text'],
                ],
            ],
            'header' => [
                'label' => 'Header',
                'fields' => [
                    ['key' => 'logo_text', 'label' => 'Logo text', 'type' => 'text'],
                    ['key' => 'logo_url', 'label' => 'Logo URL', 'type' => 'text'],
                    ['key' => 'sticky', 'label' => 'Sticky header', 'type' => 'checkbox'],
                ],
            ],
            'hero' => [
                'label' => 'Hero',
                'fields' => [
                    ['key' => 'show', 'label' => 'Show hero', 'type' => 'checkbox'],
                    ['key' => 'heading', 'label' => 'Heading', 'type' => 'text'],
                    ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'textarea'],
                    ['key' => 'cta_text', 'label' => 'Button text', 'type' => 'text'],
                    ['key' => 'cta_url', 'label' => 'Button link', 'type' => 'text'],
                    ['key' => 'background_color', 'label' => 'Background color', 'type' => 'color'],
                ],
            ],
            'home' => [
                'label' => 'Homepage',
                'fields' => [
                    ['key' => 'featured_collections_count', 'label' => 'Featured collections count', 'type' => 'text'],
                    ['key' => 'featured_products_count', 'label' => 'Featured products count', 'type' => 'text'],
                    ['key' => 'show_newsletter', 'label' => 'Show newsletter', 'type' => 'checkbox'],
                ],
            ],
            'footer' => [
                'label' => 'Footer',
                'fields' => [
                    ['key' => 'store_address', 'label' => 'Store address', 'type' => 'textarea'],
                    ['key' => 'contact_email', 'label' => 'Contact email', 'type' => 'text'],
                    ['key' => 'footer_text', 'label' => 'Footer text', 'type' => 'text'],
                ],
            ],
            'styling' => [
                'label' => 'Styling',
                'fields' => [
                    ['key' => 'primary_color', 'label' => 'Primary color', 'type' => 'color'],
                    ['key' => 'secondary_color', 'label' => 'Secondary color', 'type' => 'color'],
                    ['key' => 'font_family', 'label' => 'Font family', 'type' => 'select', 'options' => ['Inter, sans-serif' => 'Inter', 'Georgia, serif' => 'Georgia', 'monospace' => 'Monospace']],
                    ['key' => 'layout', 'label' => 'Layout', 'type' => 'select', 'options' => ['centered' => 'Centered', 'full_width' => 'Full width']],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function sectionDefaults(string $sectionKey): array
    {
        $defaults = [];

        foreach ($this->sections()[$sectionKey]['fields'] ?? [] as $field) {
            $defaults[$field['key']] = $field['type'] === 'checkbox' ? false : '';
        }

        return $defaults;
    }

    protected function persistSection(): void
    {
        if ($this->selectedSection !== null) {
            $this->allSettings[$this->selectedSection] = $this->sectionSettings;
        }
    }
}
