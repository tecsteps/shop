<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Theme;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    use ResolvesAdminStore;

    public ?int $deletingId = null;

    public bool $showDeleteModal = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', Theme::class);
    }

    public function publishTheme(int $themeId): void
    {
        $theme = $this->findTheme($themeId);

        Gate::authorize('publish', $theme);

        Theme::query()
            ->where('store_id', $this->store()->getKey())
            ->where('status', ThemeStatus::Published)
            ->update(['status' => ThemeStatus::Draft->value]);

        $theme->update(['status' => ThemeStatus::Published, 'published_at' => now()]);

        $this->toast('success', 'Theme published.');
    }

    public function duplicateTheme(int $themeId): void
    {
        $theme = $this->findTheme($themeId);

        Gate::authorize('create', Theme::class);

        $copy = Theme::query()->create([
            'store_id' => $this->store()->getKey(),
            'name' => $theme->name.' (copy)',
            'version' => $theme->version,
            'status' => ThemeStatus::Draft,
            'published_at' => null,
        ]);

        if ($theme->themeSetting !== null) {
            $copy->themeSetting()->create(['settings_json' => $theme->themeSetting->settings_json]);
        }

        $this->toast('success', 'Theme duplicated.');
    }

    public function confirmDelete(int $themeId): void
    {
        $this->deletingId = $themeId;
        $this->showDeleteModal = true;
    }

    public function deleteTheme(): void
    {
        $theme = $this->findTheme((int) $this->deletingId);

        Gate::authorize('delete', $theme);

        if ($theme->status === ThemeStatus::Published) {
            $this->showDeleteModal = false;
            $this->toast('error', 'The published theme cannot be deleted.');

            return;
        }

        $theme->themeSetting?->delete();
        $theme->themeFiles()->delete();
        $theme->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->toast('success', 'Theme deleted.');
    }

    public function render()
    {
        return view('livewire.admin.themes.index', [
            'themes' => Theme::query()
                ->where('store_id', $this->store()->getKey())
                ->with('themeSetting')
                ->orderByDesc('published_at')
                ->orderBy('id')
                ->get(),
        ])->layout('livewire.admin.layout.app', ['title' => 'Themes']);
    }

    protected function findTheme(int $themeId): Theme
    {
        return Theme::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($themeId)
            ->firstOrFail();
    }
}
