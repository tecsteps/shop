<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function publish(int $themeId): void
    {
        $theme = Theme::query()->findOrFail($themeId);
        $this->authorize('publish', $theme);
        Theme::query()->where('store_id', $theme->store_id)->whereKeyNot($theme->id)->where('status', ThemeStatus::Published)->update(['status' => ThemeStatus::Draft]);
        $theme->update(['status' => ThemeStatus::Published]);
        $this->dispatch('toast', message: 'Theme published.');
    }

    public function duplicate(int $themeId): void
    {
        $theme = Theme::query()->with(['files', 'settings'])->findOrFail($themeId);
        $this->authorize('create', Theme::class);
        $copy = $theme->replicate(['status']);
        $copy->name = $theme->name.' copy';
        $copy->status = ThemeStatus::Draft;
        $copy->save();
        foreach ($theme->files as $file) {
            $copy->files()->create($file->only(['path', 'content', 'storage_key', 'sha256', 'byte_size']));
        }
        $copy->settings()->create(['settings_json' => $theme->settings?->settings_json ?? []]);
        $this->dispatch('toast', message: 'Theme duplicated.');
    }

    public function delete(int $themeId): void
    {
        $theme = Theme::query()->findOrFail($themeId);
        $this->authorize('delete', $theme);
        abort_if($theme->status === ThemeStatus::Published, 422, 'Publish another theme before deleting this one.');
        $theme->delete();
    }

    public function render(): View
    {
        return view('livewire.admin.themes.index', ['themes' => Theme::query()->latest()->get()])->layout('layouts.admin');
    }
}
