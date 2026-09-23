<?php

namespace App\Livewire\Admin\Themes;

use App\Models\Store;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public function publishTheme(int $themeId): void
    {
        Gate::authorize('manage-themes');

        $storeId = $this->currentStore()->getKey();
        $theme = $this->findTheme($themeId, $storeId);
        $publishedAt = now();

        DB::transaction(function () use ($storeId, $theme, $publishedAt): void {
            Theme::query()
                ->where('store_id', $storeId)
                ->where('id', '!=', $theme->getKey())
                ->update(['status' => 'draft', 'is_active' => false]);

            $theme->update([
                'status' => 'published',
                'is_active' => true,
                'published_at' => $publishedAt,
            ]);
        });

        session()->flash('status', "{$theme->name} is now the published theme.");
    }

    public function duplicateTheme(int $themeId): void
    {
        Gate::authorize('manage-themes');

        $theme = $this->findTheme($themeId, $this->currentStore()->getKey());
        $copy = DB::transaction(function () use ($theme): Theme {
            $copy = $theme->replicate(['is_active', 'published_at', 'status']);
            $copy->name = mb_substr($theme->name.' (Copy)', 0, 255);
            $copy->status = 'draft';
            $copy->is_active = false;
            $copy->published_at = null;
            $copy->save();

            if ($theme->settings !== null) {
                $copy->settings()->create([
                    'settings_json' => $theme->settings->settings_json ?? [],
                    'updated_at' => now(),
                ]);
            }

            $now = now();
            $files = DB::table('theme_files')
                ->where('theme_id', $theme->getKey())
                ->get(['path', 'content', 'sha256', 'byte_size'])
                ->map(fn (object $file): array => [
                    'theme_id' => $copy->getKey(),
                    'path' => $file->path,
                    'content' => $file->content,
                    'storage_key' => 'themes/'.$copy->getKey().'/'.$file->path,
                    'sha256' => $file->sha256 ?: hash('sha256', $file->content),
                    'byte_size' => $file->byte_size ?? strlen($file->content),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            if ($files !== []) {
                DB::table('theme_files')->insert($files);
            }

            return $copy;
        });

        session()->flash('status', "{$theme->name} was duplicated.");
        $this->dispatch('theme-duplicated', themeId: $copy->getKey());
    }

    public function deleteTheme(int $themeId): void
    {
        Gate::authorize('manage-themes');

        $theme = $this->findTheme($themeId, $this->currentStore()->getKey());
        $name = $theme->name;
        $theme->delete();

        session()->flash('status', "{$name} was deleted.");
    }

    public function render(): mixed
    {
        Gate::authorize('manage-themes');

        $themes = Theme::query()
            ->where('store_id', $this->currentStore()->getKey())
            ->with('settings')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return view('livewire.admin.themes.index', compact('themes'))->layout('layouts.admin');
    }

    private function currentStore(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function findTheme(int $themeId, int|string $storeId): Theme
    {
        return Theme::query()
            ->where('store_id', $storeId)
            ->findOrFail($themeId);
    }
}
