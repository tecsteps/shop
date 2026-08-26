<?php

namespace App\Livewire\Admin\Themes;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Theme;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public function mount(): void
    {
        $this->authorize('viewAny', Theme::class);
    }

    #[Computed]
    public function themes(): Collection
    {
        return app('current_store')->themes()
            ->with('settings')
            ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->get();
    }

    public function publishTheme(int $themeId): void
    {
        $theme = Theme::find($themeId);

        if (! $theme) {
            return;
        }

        $this->authorize('publish', $theme);

        app('current_store')->themes()
            ->where('id', '!=', $themeId)
            ->where('status', 'published')
            ->update(['status' => 'draft']);

        $theme->update(['status' => 'published', 'published_at' => now()]);

        $this->toast('Theme published');
    }

    public function duplicateTheme(int $themeId): void
    {
        $theme = Theme::find($themeId);

        if (! $theme) {
            return;
        }

        $this->authorize('create', Theme::class);

        $copy = $theme->replicate(['status', 'published_at']);
        $copy->name = $theme->name.' (Copy)';
        $copy->version = $theme->version;
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->save();

        if ($theme->settings) {
            $copy->settings()->create([
                'settings_json' => $theme->settings->settings_json,
                'updated_at' => now(),
            ]);
        }

        $this->toast('Theme duplicated');
    }

    public function deleteTheme(int $themeId): void
    {
        $theme = Theme::find($themeId);

        if (! $theme) {
            return;
        }

        if ($theme->status === 'published') {
            $this->toast('The published theme cannot be deleted', 'error');

            return;
        }

        $this->authorize('delete', $theme);

        $theme->delete();

        $this->toast('Theme deleted');
    }

    public function render()
    {
        return view('livewire.admin.themes.index');
    }
}
