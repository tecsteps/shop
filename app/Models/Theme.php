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
    /** @use HasFactory<\Database\Factories\ThemeFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'is_active',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'status' => ThemeStatus::class,
        ];
    }

    /** @return HasMany<ThemeFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(ThemeFile::class);
    }

    /** @return HasOne<ThemeSettings, $this> */
    public function themeSettings(): HasOne
    {
        return $this->hasOne(ThemeSettings::class);
    }
}
