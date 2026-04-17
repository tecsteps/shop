<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Livewire\Component;

class Editor extends Component
{
    public Theme $theme;

    /** @var array<int, array{key: string, label: string, fields: array<int, array{key: string, label: string, type: string, options?: array<string, string>}>}> */
    public array $sections = [];

    public ?string $selectedSection = null;

    /** @var array<string, mixed> */
    public array $sectionSettings = [];

    public string $previewUrl = '/';

    public function mount(Theme $theme): void
    {
        $this->theme = $theme;

        $allSettings = $theme->settings?->settings_json ?? [];

        $this->sections = $allSettings['sections'] ?? [
            [
                'key' => 'header',
                'label' => 'Header',
                'fields' => [
                    ['key' => 'logo_text', 'label' => 'Logo text', 'type' => 'text'],
                    ['key' => 'bg_color', 'label' => 'Background color', 'type' => 'color'],
                    ['key' => 'show_search', 'label' => 'Show search', 'type' => 'checkbox'],
                ],
            ],
            [
                'key' => 'footer',
                'label' => 'Footer',
                'fields' => [
                    ['key' => 'copyright_text', 'label' => 'Copyright text', 'type' => 'text'],
                    ['key' => 'show_social', 'label' => 'Show social links', 'type' => 'checkbox'],
                ],
            ],
        ];

        if (! empty($this->sections)) {
            $this->selectedSection = $this->sections[0]['key'];
            $this->loadSectionSettings();
        }
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
        $this->persistSettings();
        $this->dispatch('toast', type: 'success', message: 'Theme settings saved.');
    }

    public function publish(): void
    {
        $this->persistSettings();

        Theme::query()->update(['is_active' => false, 'status' => ThemeStatus::Draft]);
        $this->theme->update([
            'is_active' => true,
            'status' => ThemeStatus::Published,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Theme published.');
    }

    public function refreshPreview(): void
    {
        $this->dispatch('refresh-preview');
    }

    private function loadSectionSettings(): void
    {
        $allSettings = $this->theme->settings?->settings_json ?? [];
        $values = $allSettings['values'] ?? [];
        $this->sectionSettings = $values[$this->selectedSection] ?? [];
    }

    private function persistSettings(): void
    {
        $allSettings = $this->theme->settings?->settings_json ?? [];
        $values = $allSettings['values'] ?? [];
        $values[$this->selectedSection] = $this->sectionSettings;

        $allSettings['values'] = $values;
        if (! isset($allSettings['sections'])) {
            $allSettings['sections'] = $this->sections;
        }

        $this->theme->settings()->updateOrCreate(
            ['theme_id' => $this->theme->id],
            ['settings_json' => $allSettings]
        );
    }

    public function render()
    {
        return view('livewire.admin.themes.editor')
            ->layout('layouts.admin', ['title' => "Edit Theme: {$this->theme->name}"]);
    }
}
