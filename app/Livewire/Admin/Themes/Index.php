<?php

namespace App\Livewire\Admin\Themes;

use App\Livewire\Admin\AdminComponent;
use App\Models\Theme;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Index extends AdminComponent
{
    public function mount(): void
    {
        Gate::authorize('viewAny', Theme::class);
    }

    public function publish(int $id): void
    {
        $theme = Theme::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($id);
        Gate::authorize('publish', $theme);
        Theme::query()->where('store_id', $this->currentStore()->getKey())->update(['status' => 'draft', 'published_at' => null]);
        $theme->update(['status' => 'published', 'published_at' => now()]);
        $this->toast('Theme published.');
    }

    public function duplicate(int $id): void
    {
        $theme = Theme::query()->where('store_id', $this->currentStore()->getKey())->with('settings')->findOrFail($id);
        Gate::authorize('create', Theme::class);
        $copy = $theme->replicate(['status', 'published_at']);
        $copy->name = $theme->name.' copy';
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->save();
        if ($theme->settings) {
            $copy->settings()->create(['settings_json' => $theme->settings->settings_json]);
        } $this->toast('Theme duplicated.');
    }

    #[Computed]
    public function themes()
    {
        return Theme::query()->where('store_id', $this->currentStore()->getKey())->with('settings')->latest()->get();
    }

    public function render()
    {
        return view('livewire.admin.themes.index');
    }
}
