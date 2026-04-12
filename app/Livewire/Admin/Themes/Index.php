<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public function publish(int $themeId): void
    {
        /** @var Store $store */
        $store = app('current_store');

        DB::transaction(function () use ($store, $themeId): void {
            Theme::query()
                ->where('store_id', $store->id)
                ->where('status', ThemeStatus::Published->value)
                ->update(['status' => ThemeStatus::Draft->value, 'published_at' => null]);

            Theme::query()
                ->where('store_id', $store->id)
                ->where('id', $themeId)
                ->update(['status' => ThemeStatus::Published->value, 'published_at' => now()]);
        });

        session()->flash('status', 'Theme published.');
    }

    public function duplicate(int $themeId): void
    {
        /** @var Store $store */
        $store = app('current_store');

        $source = Theme::query()->where('store_id', $store->id)->findOrFail($themeId);

        Theme::create([
            'store_id' => $store->id,
            'name' => $source->name.' Copy',
            'version' => $source->version,
            'status' => ThemeStatus::Draft->value,
        ]);

        session()->flash('status', 'Theme duplicated.');
    }

    public function delete(int $themeId): void
    {
        $theme = Theme::query()->findOrFail($themeId);

        if ($theme->status === ThemeStatus::Published) {
            session()->flash('status', 'Cannot delete the published theme.');

            return;
        }

        $theme->delete();
        session()->flash('status', 'Theme deleted.');
    }

    public function render(): View
    {
        $themes = Theme::query()
            ->orderByDesc('status')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.themes.index', [
            'themes' => $themes,
        ]);
    }
}
