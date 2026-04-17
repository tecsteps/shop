<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Theme::class);
    }

    public function publish(int $themeId): void
    {
        $theme = Theme::query()->findOrFail($themeId);
        $this->authorize('publish', $theme);

        DB::transaction(function () use ($theme): void {
            Theme::query()
                ->where('store_id', $theme->store_id)
                ->where('id', '!=', $theme->getKey())
                ->update(['status' => ThemeStatus::Draft->value]);

            $theme->status = ThemeStatus::Published;
            $theme->published_at = now();
            $theme->save();
        });

        session()->flash('status', 'Theme published.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Theme::class);

        return view('livewire.admin.themes.index', [
            'themes' => Theme::query()->orderByDesc('updated_at')->get(),
        ]);
    }
}
