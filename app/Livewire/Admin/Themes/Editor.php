<?php

namespace App\Livewire\Admin\Themes;

use App\Livewire\Admin\AdminComponent;
use App\Models\Theme;
use Illuminate\Support\Facades\Gate;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Editor extends AdminComponent
{
    public int $themeId;

    public string $name = '';

    public string $primaryColor = '#18181b';

    public string $accentColor = '#2563eb';

    public string $logoUrl = '';

    public string $headingFont = 'Inter';

    public string $bodyFont = 'Inter';

    public function mount(Theme $theme): void
    {
        abort_unless($theme->store_id === $this->currentStore()->getKey(), 404);
        Gate::authorize('view', $theme);
        $this->themeId = $theme->getKey();
        $this->name = $theme->name;
        $settings = $theme->settings?->settings_json ?? [];
        $this->primaryColor = $settings['primary_color'] ?? $this->primaryColor;
        $this->accentColor = $settings['accent_color'] ?? $this->accentColor;
        $this->logoUrl = $settings['logo_url'] ?? '';
        $this->headingFont = $settings['heading_font'] ?? 'Inter';
        $this->bodyFont = $settings['body_font'] ?? 'Inter';
    }

    public function save(): void
    {
        $validated = $this->validate(['name' => ['required', 'string', 'max:255'], 'primaryColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'accentColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'logoUrl' => ['nullable', 'url'], 'headingFont' => ['required', 'in:Inter,Georgia,Arial'], 'bodyFont' => ['required', 'in:Inter,Georgia,Arial']]);
        $theme = Theme::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($this->themeId);
        Gate::authorize('update', $theme);
        $theme->update(['name' => $validated['name']]);
        $theme->settings()->updateOrCreate(['theme_id' => $theme->getKey()], ['settings_json' => ['primary_color' => $validated['primaryColor'], 'accent_color' => $validated['accentColor'], 'logo_url' => $validated['logoUrl'], 'heading_font' => $validated['headingFont'], 'body_font' => $validated['bodyFont']]]);
        $this->toast('Theme saved.');
    }

    public function render()
    {
        return view('livewire.admin.themes.editor');
    }
}
