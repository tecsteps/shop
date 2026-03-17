<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use App\Models\ThemeSettings;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Editor extends Component
{
    public Theme $theme;

    /** @var array<string, array{label: string, fields: array<string, array{type: string, label: string, default: mixed}>}> */
    public array $sections = [];

    public ?string $selectedSection = null;

    /** @var array<string, mixed> */
    public array $sectionSettings = [];

    public string $previewUrl = '/';

    public function mount(Theme $theme): void
    {
        $this->theme = $theme;
        $this->previewUrl = '/';

        // Load sections from theme settings schema
        $schema = $theme->settings_schema ?? [];
        $this->sections = is_array($schema) ? $schema : [];

        if (! empty($this->sections)) {
            $firstKey = array_key_first($this->sections);
            $this->selectSection($firstKey);
        }
    }

    public function selectSection(string $sectionKey): void
    {
        $this->selectedSection = $sectionKey;

        // Load saved settings for this section
        $settings = ThemeSettings::withoutGlobalScopes()
            ->where('theme_id', $this->theme->id)
            ->where('section_key', $sectionKey)
            ->first();

        $this->sectionSettings = $settings?->values ?? [];

        // Fill defaults
        if (isset($this->sections[$sectionKey]['fields'])) {
            foreach ($this->sections[$sectionKey]['fields'] as $key => $field) {
                if (! isset($this->sectionSettings[$key])) {
                    $this->sectionSettings[$key] = $field['default'] ?? '';
                }
            }
        }
    }

    public function updateSetting(string $key, mixed $value): void
    {
        $this->sectionSettings[$key] = $value;
    }

    public function save(): void
    {
        if ($this->selectedSection) {
            ThemeSettings::withoutGlobalScopes()->updateOrCreate(
                [
                    'theme_id' => $this->theme->id,
                    'section_key' => $this->selectedSection,
                ],
                ['values' => $this->sectionSettings]
            );
        }

        $this->dispatch('toast', type: 'success', message: 'Theme settings saved.');
    }

    public function publish(): void
    {
        $this->save();

        Theme::withoutGlobalScopes()
            ->where('store_id', $this->theme->store_id)
            ->update(['is_published' => false]);

        $this->theme->update(['is_published' => true]);

        $this->dispatch('toast', type: 'success', message: 'Theme published.');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.themes.editor');
    }
}
