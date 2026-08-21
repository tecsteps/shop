<?php

namespace App\Models;

use App\Enums\ThemeStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Theme extends Model
{
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\ThemeFactory> */
    use HasFactory;

    protected $fillable = ['store_id', 'name', 'status', 'version', 'published_at'];

    protected function casts(): array
    {
        return ['status' => ThemeStatus::class, 'published_at' => 'datetime'];
    }

    public function files(): HasMany
    {
        return $this->hasMany(ThemeFile::class);
    }

    public function themeSettings(): HasOne
    {
        return $this->hasOne(ThemeSetting::class, 'theme_id', 'id');
    }

    public function settings(): HasOne
    {
        return $this->themeSettings();
    }

    public function settingsRows(): HasOne
    {
        return $this->themeSettings();
    }
}
