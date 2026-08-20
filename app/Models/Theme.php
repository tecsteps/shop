<?php

namespace App\Models;

use App\Enums\ThemeStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'name', 'status', 'version', 'settings'];

    protected function casts(): array
    {
        return ['status' => ThemeStatus::class, 'settings' => 'array'];
    }

    public function files(): HasMany
    {
        return $this->hasMany(ThemeFile::class);
    }

    public function settingsRows(): HasMany
    {
        return $this->hasMany(ThemeSetting::class);
    }
}
