<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Editor extends Component
{
    public Theme $theme;

    /** @var array<string, mixed> */
    public array $settings = [];

    public function mount(Theme $theme): void
    {
        $this->theme = $theme;
        $existing = $theme->settings?->settings_json ?? [];
        $this->settings = array_merge([
            'hero_heading' => '',
            'hero_subheading' => '',
            'featured_collection_handles' => '',
            'featured_product_handles' => '',
            'primary_color' => '#0f172a',
            'accent_color' => '#0ea5e9',
            'dark_mode' => 'auto',
        ], $existing);
        if (is_array($this->settings['featured_collection_handles'] ?? null)) {
            $this->settings['featured_collection_handles'] = implode(',', $this->settings['featured_collection_handles']);
        }
        if (is_array($this->settings['featured_product_handles'] ?? null)) {
            $this->settings['featured_product_handles'] = implode(',', $this->settings['featured_product_handles']);
        }
    }

    public function save(): void
    {
        $persist = $this->settings;
        $persist['featured_collection_handles'] = array_values(array_filter(array_map('trim', explode(',', (string) $persist['featured_collection_handles']))));
        $persist['featured_product_handles'] = array_values(array_filter(array_map('trim', explode(',', (string) $persist['featured_product_handles']))));

        ThemeSettings::query()->updateOrInsert(
            ['theme_id' => $this->theme->id],
            [
                'settings_json' => json_encode($persist),
                'updated_at' => now(),
            ],
        );

        app(ThemeSettingsService::class)->forget($this->theme->store);

        session()->flash('success', 'Theme saved.');
    }

    public function render()
    {
        return view('livewire.admin.themes.editor');
    }
}
