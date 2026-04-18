<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public function publish(int $id): void
    {
        $store = app('current_store');
        Theme::query()->where('store_id', $store->id)->update(['status' => ThemeStatus::Draft->value]);
        Theme::query()->whereKey($id)->update([
            'status' => ThemeStatus::Published->value,
            'published_at' => now(),
        ]);
    }

    public function duplicate(int $id): void
    {
        $theme = Theme::query()->findOrFail($id);
        $copy = $theme->replicate(['published_at']);
        $copy->status = ThemeStatus::Draft;
        $copy->name = $theme->name.' (copy)';
        $copy->published_at = null;
        $copy->save();
    }

    public function delete(int $id): void
    {
        Theme::query()->whereKey($id)->where('status', '!=', ThemeStatus::Published->value)->delete();
    }

    public function render()
    {
        $store = app('current_store');
        $themes = Theme::query()->where('store_id', $store->id)->orderByDesc('id')->get();

        return view('livewire.admin.themes.index', [
            'themes' => $themes,
        ]);
    }
}
