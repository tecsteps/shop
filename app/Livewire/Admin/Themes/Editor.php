<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Theme;
use App\Services\ThemeSettingsService;
use Illuminate\Support\Arr;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Theme editor: a three-panel layout (sections / live preview / settings). Each
 * section maps to a slice of the theme settings tree; field definitions drive
 * the dynamic settings form. Saving writes the merged settings back to
 * theme_settings and flushes the cache so the preview reflects changes.
 */
#[Layout('livewire.admin.layout.app')]
class Editor extends Component
{
    use BindsCurrentStore;

    public Theme $theme;

    public ?string $selectedSection = 'header';

    /**
     * The working copy of the full settings tree (defaults merged with stored).
     *
     * @var array<string, mixed>
     */
    public array $settings = [];

    public function mount(Theme $theme, ThemeSettingsService $themeSettings): void
    {
        $this->authorize('update', $theme);
        $this->theme = $theme;
        $this->settings = $themeSettings->all(app('current_store'));
    }

    /**
     * Section definitions: key => [label, field definitions]. Each field maps a
     * dot-path within the settings tree to an input type.
     *
     * @return array<string, array{label: string, fields: array<int, array{key: string, label: string, type: string, options?: array<string,string>}>}>
     */
    public function getSectionsProperty(): array
    {
        return [
            'header' => [
                'label' => __('Header'),
                'fields' => [
                    ['key' => 'header.sticky', 'label' => __('Sticky header'), 'type' => 'checkbox'],
                    ['key' => 'header.logo_url', 'label' => __('Logo URL'), 'type' => 'text'],
                ],
            ],
            'announcement' => [
                'label' => __('Announcement bar'),
                'fields' => [
                    ['key' => 'announcement.enabled', 'label' => __('Enabled'), 'type' => 'checkbox'],
                    ['key' => 'announcement.text', 'label' => __('Text'), 'type' => 'text'],
                    ['key' => 'announcement.background_color', 'label' => __('Background color'), 'type' => 'color'],
                ],
            ],
            'hero' => [
                'label' => __('Hero'),
                'fields' => [
                    ['key' => 'home.hero.heading', 'label' => __('Heading'), 'type' => 'text'],
                    ['key' => 'home.hero.subheading', 'label' => __('Subheading'), 'type' => 'textarea'],
                    ['key' => 'home.hero.cta_label', 'label' => __('Button label'), 'type' => 'text'],
                    ['key' => 'home.hero.cta_url', 'label' => __('Button URL'), 'type' => 'text'],
                ],
            ],
            'colors' => [
                'label' => __('Colors'),
                'fields' => [
                    ['key' => 'colors.primary', 'label' => __('Primary'), 'type' => 'color'],
                    ['key' => 'colors.secondary', 'label' => __('Secondary'), 'type' => 'color'],
                    ['key' => 'colors.accent', 'label' => __('Accent'), 'type' => 'color'],
                ],
            ],
            'footer' => [
                'label' => __('Footer'),
                'fields' => [
                    ['key' => 'footer.description', 'label' => __('Description'), 'type' => 'textarea'],
                    ['key' => 'dark_mode', 'label' => __('Dark mode'), 'type' => 'select', 'options' => ['system' => 'System', 'light' => 'Light', 'dark' => 'Dark']],
                ],
            ],
        ];
    }

    public function selectSection(string $sectionKey): void
    {
        $this->selectedSection = $sectionKey;
    }

    public function getPreviewUrlProperty(): string
    {
        return route('storefront.home');
    }

    public function save(ThemeSettingsService $themeSettings): void
    {
        $this->authorize('update', $this->theme);

        $this->theme->settings()->updateOrCreate(
            ['theme_id' => $this->theme->id],
            ['settings_json' => $this->settings],
        );

        $themeSettings->forget(app('current_store')->id);

        $this->dispatch('toast', type: 'success', message: __('Theme saved'));
    }

    public function publish(ThemeSettingsService $themeSettings): void
    {
        $this->authorize('publish', $this->theme);
        $this->save($themeSettings);

        Theme::query()->where('status', ThemeStatus::Published->value)->update(['status' => ThemeStatus::Draft->value]);
        $this->theme->update(['status' => ThemeStatus::Published->value, 'published_at' => now()]);
        $themeSettings->forget(app('current_store')->id);

        $this->dispatch('toast', type: 'success', message: __('Theme published'));
    }

    /**
     * Read a setting value by dot-path from the working copy.
     */
    public function value(string $key): mixed
    {
        return Arr::get($this->settings, $key);
    }

    public function render()
    {
        return view('livewire.admin.themes.editor');
    }
}
