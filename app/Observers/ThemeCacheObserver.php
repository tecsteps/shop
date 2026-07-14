<?php

namespace App\Observers;

use App\Models\Theme;
use App\Models\ThemeSettings;
use Illuminate\Support\Facades\Cache;

final class ThemeCacheObserver
{
    public function saved(Theme|ThemeSettings $model): void
    {
        $this->forget($model);
    }

    public function deleted(Theme|ThemeSettings $model): void
    {
        $this->forget($model);
    }

    private function forget(Theme|ThemeSettings $model): void
    {
        $storeId = $model instanceof Theme
            ? $model->store_id
            : Theme::withoutGlobalScopes()->whereKey($model->theme_id)->value('store_id');
        if ($storeId !== null) {
            Cache::forget("theme-settings:{$storeId}");
        }
    }
}
