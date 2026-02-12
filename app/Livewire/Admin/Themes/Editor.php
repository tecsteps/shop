<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Theme Editor')]
class Editor extends Component
{
    public Theme $theme;

    /** @var array<int, array<string, mixed>> */
    public array $sections = [];

    public ?string $selectedSection = null;

    /** @var array<string, array<string, mixed>> */
    public array $sectionSettings = [];

    public function mount(Theme $theme): void
    {
        $store = app('current_store');
        abort_unless($theme->store_id === $store->id, 404);

        $this->theme = $theme;

        $settings = $theme->settings;
        $settingsData = $settings?->settings_json ?? [];

        $this->sections = $settingsData['sections'] ?? [
            ['key' => 'header', 'label' => 'Header', 'fields' => [
                ['key' => 'logo_text', 'label' => 'Logo text', 'type' => 'text', 'default' => ''],
                ['key' => 'show_search', 'label' => 'Show search', 'type' => 'checkbox', 'default' => true],
            ]],
            ['key' => 'hero', 'label' => 'Hero', 'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'default' => ''],
                ['key' => 'subheading', 'label' => 'Subheading', 'type' => 'textarea', 'default' => ''],
                ['key' => 'background_color', 'label' => 'Background color', 'type' => 'color', 'default' => '#ffffff'],
            ]],
            ['key' => 'featured_products', 'label' => 'Featured products', 'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'type' => 'text', 'default' => 'Featured Products'],
                ['key' => 'products_count', 'label' => 'Products to show', 'type' => 'select', 'default' => '4', 'options' => ['4' => '4', '8' => '8', '12' => '12']],
            ]],
            ['key' => 'footer', 'label' => 'Footer', 'fields' => [
                ['key' => 'copyright_text', 'label' => 'Copyright text', 'type' => 'text', 'default' => ''],
                ['key' => 'show_social_links', 'label' => 'Show social links', 'type' => 'checkbox', 'default' => false],
            ]],
        ];

        $this->sectionSettings = $settingsData['values'] ?? [];
    }

    public function selectSection(string $sectionKey): void
    {
        $this->selectedSection = $sectionKey;
    }

    public function updateSetting(string $key, mixed $value): void
    {
        if ($this->selectedSection === null) {
            return;
        }

        $this->sectionSettings[$this->selectedSection][$key] = $value;
    }

    public function save(): void
    {
        ThemeSettings::updateOrCreate(
            ['theme_id' => $this->theme->id],
            ['settings_json' => [
                'sections' => $this->sections,
                'values' => $this->sectionSettings,
            ]]
        );

        $this->dispatch('toast', type: 'success', message: 'Theme settings saved.');
    }

    public function publish(): void
    {
        $this->save();

        $store = app('current_store');

        Theme::query()
            ->where('store_id', $store->id)
            ->where('status', ThemeStatus::Published)
            ->update(['status' => ThemeStatus::Draft, 'published_at' => null]);

        $this->theme->update([
            'status' => ThemeStatus::Published,
            'published_at' => now(),
        ]);

        $this->dispatch('toast', type: 'success', message: 'Theme published.');
    }

    public function render(): View
    {
        return view('livewire.admin.themes.editor');
    }
}
