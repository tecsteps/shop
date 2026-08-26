<?php

namespace App\Livewire\Admin\Themes;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Theme;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Editor extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public Theme $theme;

    /**
     * @var list<array{key: string, label: string, fields: list<array{key: string, label: string, type: string, options?: array<mixed, string>}>}>
     */
    public array $sections = [];

    public ?string $selectedSection = null;

    /** @var array<string, mixed> */
    public array $sectionSettings = [];

    public string $previewUrl = '';

    public function mount(Theme $theme): void
    {
        $this->authorize('update', $theme);

        $this->theme = $theme;
        $this->previewUrl = route('storefront.home');

        $this->sections = [
            [
                'key' => 'header',
                'label' => 'Header',
                'fields' => [
                    ['key' => 'logo_text', 'label' => 'Logo text', 'type' => 'text'],
                    ['key' => 'background_color', 'label' => 'Background color', 'type' => 'color'],
                    ['key' => 'show_search', 'label' => 'Show search', 'type' => 'checkbox'],
                    ['key' => 'layout', 'label' => 'Layout', 'type' => 'select', 'options' => ['centered' => 'Centered', 'left' => 'Left aligned', 'split' => 'Split']],
                ],
            ],
            [
                'key' => 'hero',
                'label' => 'Hero',
                'fields' => [
                    ['key' => 'heading', 'label' => 'Heading', 'type' => 'text'],
                    ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'textarea'],
                    ['key' => 'button_text', 'label' => 'Button text', 'type' => 'text'],
                    ['key' => 'button_url', 'label' => 'Button URL', 'type' => 'text'],
                ],
            ],
            [
                'key' => 'products',
                'label' => 'Products',
                'fields' => [
                    ['key' => 'section_title', 'label' => 'Section title', 'type' => 'text'],
                    ['key' => 'products_per_row', 'label' => 'Products per row', 'type' => 'select', 'options' => [2 => '2', 3 => '3', 4 => '4']],
                    ['key' => 'show_compare_at_price', 'label' => 'Show compare-at prices', 'type' => 'checkbox'],
                ],
            ],
            [
                'key' => 'footer',
                'label' => 'Footer',
                'fields' => [
                    ['key' => 'copyright_text', 'label' => 'Copyright text', 'type' => 'text'],
                    ['key' => 'show_payment_icons', 'label' => 'Show payment icons', 'type' => 'checkbox'],
                ],
            ],
        ];

        $this->selectedSection = $this->sections[0]['key'];
        $this->loadSectionSettings();
    }

    #[Computed]
    public function selectedFields(): array
    {
        $section = collect($this->sections)->firstWhere('key', $this->selectedSection);

        return $section['fields'] ?? [];
    }

    public function loadSectionSettings(): void
    {
        $settings = $this->theme->settings?->settings_json ?? [];
        $defaults = [];

        foreach ($this->selectedFields as $field) {
            $defaults[$field['key']] = match ($field['type']) {
                'checkbox' => false,
                'select' => array_key_first($field['options'] ?? []),
                default => '',
            };
        }

        $this->sectionSettings = array_merge($defaults, $settings[$this->selectedSection] ?? []);
    }

    public function selectSection(string $sectionKey): void
    {
        $this->selectedSection = $sectionKey;
        $this->loadSectionSettings();
    }

    public function updateSetting(string $key, mixed $value): void
    {
        $this->sectionSettings[$key] = $value;
    }

    public function save(): void
    {
        $this->authorize('update', $this->theme);

        $this->persistSettings();

        $this->toast('Theme settings saved');
    }

    public function publish(): void
    {
        $this->authorize('publish', $this->theme);

        $this->persistSettings();

        app('current_store')->themes()
            ->where('id', '!=', $this->theme->id)
            ->where('status', 'published')
            ->update(['status' => 'draft']);

        $this->theme->update(['status' => 'published', 'published_at' => now()]);

        $this->toast('Theme published');
    }

    public function refreshPreview(): void
    {
        $this->dispatch('refresh-preview');
    }

    private function persistSettings(): void
    {
        $settings = $this->theme->settings?->settings_json ?? [];
        $settings[$this->selectedSection] = $this->sectionSettings;

        if ($this->theme->settings) {
            $this->theme->settings()->update(['settings_json' => $settings, 'updated_at' => now()]);
        } else {
            $this->theme->settings()->create(['settings_json' => $settings, 'updated_at' => now()]);
        }
    }

    public function render()
    {
        return view('livewire.admin.themes.editor');
    }
}
