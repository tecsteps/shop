<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public bool $showCreateForm = false;

    public string $newThemeName = '';

    public bool $confirmingDelete = false;

    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Theme::class);
    }

    /**
     * Create a new draft theme with default settings (spec 03 §12.1).
     */
    public function createTheme(): void
    {
        $this->authorize('create', Theme::class);

        $validated = $this->validate([
            'newThemeName' => ['required', 'string', 'max:255'],
        ]);

        /** @var Store $store */
        $store = app('current_store');

        $theme = Theme::create([
            'store_id' => $store->id,
            'name' => $validated['newThemeName'],
            'version' => '1.0.0',
            'status' => ThemeStatus::Draft,
        ]);
        $theme->settings()->create(['settings_json' => []]);

        $this->showCreateForm = false;
        $this->newThemeName = '';

        $this->dispatch('toast', type: 'success', message: 'Theme created');
    }

    /**
     * Publish a theme, demoting every other theme to draft (spec 03 §12.1).
     */
    public function publishTheme(int $themeId): void
    {
        $theme = Theme::query()->find($themeId);

        if ($theme === null) {
            return;
        }

        $this->authorize('publish', $theme);

        $theme->publish();

        $this->dispatch('toast', type: 'success', message: 'Theme published');
    }

    /**
     * Duplicate a theme including files and settings (spec 03 §12.1).
     */
    public function duplicateTheme(int $themeId): void
    {
        $this->authorize('create', Theme::class);

        $theme = Theme::query()->find($themeId);

        if ($theme === null) {
            return;
        }

        $theme->duplicate($theme->name.' (copy)');

        $this->dispatch('toast', type: 'success', message: 'Theme duplicated');
    }

    /**
     * Open the delete confirmation modal for a theme.
     */
    public function confirmDelete(int $themeId): void
    {
        $this->deletingId = $themeId;
        $this->confirmingDelete = true;
    }

    /**
     * Delete a theme. The published theme cannot be deleted (spec 03 §12.1).
     */
    public function deleteTheme(): void
    {
        $this->confirmingDelete = false;

        $theme = Theme::query()->find($this->deletingId);
        $this->deletingId = null;

        if ($theme === null) {
            return;
        }

        $this->authorize('delete', $theme);

        if ($theme->isPublished()) {
            $this->dispatch('toast', type: 'error', message: 'The published theme cannot be deleted. Publish another theme first.');

            return;
        }

        $theme->files()->delete();
        $theme->settings()->delete();
        $theme->delete();

        $this->dispatch('toast', type: 'success', message: 'Theme deleted');
    }

    public function render(): View
    {
        return view('livewire.admin.themes.index', [
            'themes' => Theme::query()->orderByDesc('status')->orderBy('name')->get(),
        ])->layout('admin.layouts.app')->title('Themes');
    }
}
