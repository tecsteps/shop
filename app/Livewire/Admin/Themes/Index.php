<?php

namespace App\Livewire\Admin\Themes;

use App\Livewire\Admin\AdminComponent;
use App\Models\Theme;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Index extends AdminComponent
{
    public function mount(): void
    {
        $this->authorizeThemes();
        $this->authorizeAction('viewAny', Theme::class);
    }

    public function publishTheme(int $themeId): void
    {
        $this->authorizeThemes();
        $theme = $this->theme($themeId);
        $this->authorizeAction('update', $theme);

        DB::transaction(function () use ($theme): void {
            Theme::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->update([
                'status' => 'draft',
                'published_at' => null,
            ]);
            $theme->update(['status' => 'published', 'published_at' => now()]);
        });

        Cache::forget('theme-settings:'.$this->currentStore()->id);
        unset($this->themes);
        $this->toast('Theme published');
    }

    public function duplicateTheme(int $themeId): void
    {
        $this->authorizeThemes();
        $source = $this->theme($themeId)->load(['settings', 'files']);
        $this->authorizeAction('view', $source);
        $this->authorizeAction('create', Theme::class);

        DB::transaction(function () use ($source): void {
            $copy = Theme::withoutGlobalScopes()->create([
                'store_id' => $this->currentStore()->id,
                'name' => $source->name.' Copy',
                'version' => $source->version,
                'status' => 'draft',
            ]);
            $copy->settings()->create(['settings_json' => (array) ($source->settings?->settings_json ?? [])]);
            foreach ($source->files as $file) {
                $storageKey = "themes/{$this->currentStore()->id}/{$copy->id}/{$file->path}";
                if (Storage::disk('local')->exists($file->storage_key)) {
                    Storage::disk('local')->copy($file->storage_key, $storageKey);
                }
                $copy->files()->create([
                    'path' => $file->path,
                    'storage_key' => $storageKey,
                    'sha256' => $file->sha256,
                    'byte_size' => $file->byte_size,
                ]);
            }
        });

        unset($this->themes);
        $this->toast('Theme duplicated');
    }

    public function deleteTheme(int $themeId): void
    {
        $this->authorizeThemes();
        $theme = $this->theme($themeId);
        $this->authorizeAction('delete', $theme);
        abort_if($this->enumValue($theme->status) === 'published', 422, 'The published theme cannot be deleted.');
        $theme->delete();
        unset($this->themes);
        $this->toast('Theme deleted');
    }

    #[Computed]
    public function themes(): mixed
    {
        return Theme::withoutGlobalScopes()
            ->where('store_id', $this->currentStore()->id)
            ->with('settings')
            ->orderByRaw("case when status = 'published' then 0 else 1 end")
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return $this->admin(view('admin.themes.index'), 'Themes', [['label' => 'Themes']]);
    }

    private function authorizeThemes(): void
    {
        $this->requireRoles(['owner', 'admin']);
    }

    private function theme(int $id): Theme
    {
        return Theme::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->findOrFail($id);
    }
}
