<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Editor extends Component
{
    public Theme $theme;

    /** @var array<string, mixed> */
    public array $settings = [];

    /** @var array<string, string> */
    public array $sections = [];

    public string $selectedSection = '';

    public function mount(Theme $theme): void
    {
        $storeId = app('current_store')->id;
        abort_unless((int) $theme->store_id === $storeId, 404);

        $this->theme = $theme->load('settings');

        $defaults = [
            'announcement_bar' => [
                'announcement_bar_enabled' => false,
                'announcement_bar_text' => '',
                'announcement_bar_link' => '',
                'announcement_bar_bg_color' => '#1f2937',
            ],
            'header' => [
                'sticky_header' => false,
            ],
            'hero' => [
                'hero_heading' => 'Welcome to our store',
                'hero_subheading' => 'Discover our latest collection',
                'hero_cta_text' => 'Shop now',
                'hero_cta_link' => '/collections',
            ],
            'featured' => [
                'featured_collections_count' => 4,
                'featured_products_count' => 8,
            ],
            'social' => [
                'social_facebook' => '',
                'social_instagram' => '',
                'social_twitter' => '',
            ],
        ];

        $this->sections = [
            'announcement_bar' => 'Announcement Bar',
            'header' => 'Header',
            'hero' => 'Hero',
            'featured' => 'Featured Content',
            'social' => 'Social Links',
        ];

        $saved = $this->theme->settings->settings_json ?? [];

        foreach ($defaults as $section => $fields) {
            foreach ($fields as $key => $default) {
                $this->settings[$key] = $saved[$key] ?? $default;
            }
        }

        $this->selectedSection = array_key_first($this->sections);
    }

    public function selectSection(string $section): void
    {
        if (array_key_exists($section, $this->sections)) {
            $this->selectedSection = $section;
        }
    }

    public function save(): void
    {
        ThemeSettings::updateOrCreate(
            ['theme_id' => $this->theme->id],
            [
                'settings_json' => $this->settings,
                'updated_at' => now(),
            ]
        );

        $storeId = app('current_store')->id;
        Cache::forget("theme_settings:{$storeId}");

        $this->dispatch('toast', type: 'success', message: __('Theme settings saved.'));
    }

    public function render(): View
    {
        return view('livewire.admin.themes.editor');
    }
}
