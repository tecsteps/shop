<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Themes')]
class Index extends Component
{
    public function activate(int $themeId): void
    {
        $store = app('current_store');

        Theme::query()
            ->where('store_id', $store->id)
            ->where('status', ThemeStatus::Published)
            ->update(['status' => ThemeStatus::Draft]);

        Theme::query()
            ->where('store_id', $store->id)
            ->where('id', $themeId)
            ->update([
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ]);

        $this->dispatch('toast', type: 'success', message: 'Theme activated.');
    }

    public function deactivate(int $themeId): void
    {
        $store = app('current_store');

        Theme::query()
            ->where('store_id', $store->id)
            ->where('id', $themeId)
            ->update(['status' => ThemeStatus::Draft]);

        $this->dispatch('toast', type: 'success', message: 'Theme deactivated.');
    }

    public function render(): View
    {
        $store = app('current_store');

        $themes = Theme::query()
            ->where('store_id', $store->id)
            ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
            ->latest()
            ->get();

        return view('livewire.admin.themes.index', [
            'themes' => $themes,
        ]);
    }
}
