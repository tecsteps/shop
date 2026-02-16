<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Themes'])]
class Index extends Component
{
    public function activate(int $themeId): void
    {
        $store = app('current_store');
        Theme::query()->where('store_id', $store->id)->update(['is_active' => false]);
        Theme::query()->where('id', $themeId)->update(['is_active' => true, 'status' => 'published']);
        session()->flash('success', 'Theme activated.');
    }

    public function render(): mixed
    {
        $store = app('current_store');

        return view('livewire.admin.themes.index', [
            'themes' => Theme::query()->where('store_id', $store->id)->get(),
        ]);
    }
}
