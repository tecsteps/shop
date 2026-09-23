<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public string $name = '';

    public string $heroHeading = '';

    public string $heroSubheading = '';

    public string $primaryColor = '#1a1a2e';

    public function save(): void
    {
        Gate::authorize('manage-themes');
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'heroHeading' => ['required', 'string', 'max:255'],
            'heroSubheading' => ['nullable', 'string', 'max:1000'],
            'primaryColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $theme = Theme::query()->where('is_active', true)->first() ?? Theme::create(['store_id' => app('current_store')->id, 'name' => $this->name, 'status' => 'draft']);
        $theme->settings()->updateOrCreate(['theme_id' => $theme->id], ['settings_json' => [
            'hero_heading' => $this->heroHeading,
            'hero_subheading' => $this->heroSubheading,
            'primary_color' => $this->primaryColor,
        ]]);
        $theme->update(['name' => $this->name]);
        session()->flash('status', 'Theme saved.');
    }

    public function activate(int $themeId): void
    {
        Gate::authorize('manage-themes');
        Theme::query()->where('store_id', app('current_store')->id)->update(['is_active' => false]);
        Theme::query()->findOrFail($themeId)->update(['is_active' => true, 'status' => 'published']);
    }

    public function render(): mixed
    {
        Gate::authorize('manage-themes');
        $themes = Theme::query()->with('settings')->orderByDesc('is_active')->get();
        $activeSettings = $themes->firstWhere('is_active', true)?->settings?->settings_json ?? [];
        $this->heroHeading = $this->heroHeading ?: ($activeSettings['hero_heading'] ?? 'Welcome');
        $this->heroSubheading = $this->heroSubheading ?: ($activeSettings['hero_subheading'] ?? '');
        $this->primaryColor = $activeSettings['primary_color'] ?? $this->primaryColor;
        $this->name = $this->name ?: ($themes->firstWhere('is_active', true)?->name ?? 'Default Theme');

        return view('livewire.admin.themes.index', ['themes' => $themes])->layout('layouts.admin');
    }
}
