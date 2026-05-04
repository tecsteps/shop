<?php

namespace App\Livewire\Admin\Themes;

use App\Enums\ThemeStatus;
use App\Models\Store;
use App\Models\Theme;
use App\Models\ThemeFile;
use App\Models\ThemeSettings;
use App\Services\ThemeSettingsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->authorize('viewAny', Theme::class);

        $this->storeId = $store->getKey();
    }

    public function publishTheme(int $themeId, ThemeSettingsService $settings): void
    {
        $theme = $this->theme($themeId);

        $this->authorize('publish', $theme);

        DB::transaction(function () use ($theme): void {
            Theme::withoutGlobalScopes()
                ->where('store_id', $this->storeId)
                ->update([
                    'status' => ThemeStatus::Draft,
                    'published_at' => null,
                ]);

            $theme->forceFill([
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ])->save();
        });

        $settings->forget($this->store());

        session()->flash('status', 'Theme published');
        $this->dispatch('toast', type: 'success', message: __('Theme published'));
    }

    public function duplicateTheme(int $themeId): void
    {
        $theme = $this->theme($themeId);

        $this->authorize('create', Theme::class);

        DB::transaction(function () use ($theme): void {
            $copy = Theme::withoutGlobalScopes()->create([
                'store_id' => $this->storeId,
                'name' => $theme->name.' Copy',
                'version' => $theme->version,
                'status' => ThemeStatus::Draft,
                'published_at' => null,
            ]);

            $theme->files()
                ->withoutGlobalScopes()
                ->get()
                ->each(fn (ThemeFile $file): ThemeFile => ThemeFile::withoutGlobalScopes()->create([
                    'theme_id' => $copy->getKey(),
                    'path' => $file->path,
                    'storage_key' => $file->storage_key,
                    'sha256' => $file->sha256,
                    'byte_size' => $file->byte_size,
                ]));

            ThemeSettings::withoutGlobalScopes()->create([
                'theme_id' => $copy->getKey(),
                'settings_json' => $theme->settings?->settings_json ?? [],
                'updated_at' => now(),
            ]);
        });

        session()->flash('status', 'Theme duplicated');
    }

    public function deleteTheme(int $themeId): void
    {
        $theme = $this->theme($themeId);

        $this->authorize('delete', $theme);

        if ($theme->isPublished()) {
            $this->addError('theme', __('Published themes cannot be deleted.'));

            return;
        }

        $theme->delete();

        session()->flash('status', 'Theme deleted');
    }

    /**
     * @return Collection<int, Theme>
     */
    public function themes(): Collection
    {
        return Theme::withoutGlobalScopes()
            ->withCount('files')
            ->where('store_id', $this->storeId)
            ->orderByRaw("case when status = 'published' then 0 else 1 end")
            ->orderBy('name')
            ->get();
    }

    public function statusColor(Theme $theme): string
    {
        return $theme->status === ThemeStatus::Published ? 'green' : 'zinc';
    }

    public function render(): mixed
    {
        return view('livewire.admin.themes.index', [
            'themes' => $this->themes(),
        ])->layout('layouts.app', [
            'title' => __('Themes'),
        ]);
    }

    private function store(): Store
    {
        return Store::query()->whereKey($this->storeId)->firstOrFail();
    }

    private function theme(int $themeId): Theme
    {
        return Theme::withoutGlobalScopes()
            ->with(['files', 'settings'])
            ->where('store_id', $this->storeId)
            ->whereKey($themeId)
            ->firstOrFail();
    }
}
