<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Theme;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Themes')]
class Index extends Component
{
    public function publishTheme(int $themeId): void
    {
        $theme = Theme::query()->findOrFail($themeId);
        Gate::authorize('update', $theme);
        DB::transaction(function () use ($theme): void {
            Theme::query()->where('status', 'published')->update(['status' => 'draft', 'published_at' => null]);
            $theme->update(['status' => 'published', 'published_at' => now()]);
        });
        $this->dispatch('toast', type: 'success', message: 'Theme published.');
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Theme::class);

        return view('livewire.admin.themes.index', ['themes' => Theme::query()->orderByDesc('published_at')->get()]);
    }
}
