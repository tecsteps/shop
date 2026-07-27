<?php

namespace App\Models;

use App\Enums\ThemeStatus;
use App\Models\Concerns\BelongsToStore;
use App\Services\ThemeSettingsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Theme extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeFactory> */
    use BelongsToStore, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'version',
        'status',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ThemeStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * Register model event hooks.
     */
    protected static function booted(): void
    {
        // Theme changes (including publish/unpublish) affect the cached
        // storefront settings of the owning store.
        $flush = fn (Theme $theme) => app(ThemeSettingsService::class)->invalidate($theme->store_id);

        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Get the files belonging to the theme.
     *
     * @return HasMany<ThemeFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ThemeFile::class);
    }

    /**
     * Get the settings record of the theme.
     *
     * @return HasOne<ThemeSettings, $this>
     */
    public function settings(): HasOne
    {
        return $this->hasOne(ThemeSettings::class);
    }

    /**
     * Whether the theme is the published one for its store.
     */
    public function isPublished(): bool
    {
        return $this->status === ThemeStatus::Published;
    }

    /**
     * Publish this theme and demote every other theme of the store to draft.
     */
    public function publish(): void
    {
        DB::transaction(function (): void {
            static::withoutGlobalScopes()
                ->where('store_id', $this->store_id)
                ->whereKeyNot($this->getKey())
                ->where('status', ThemeStatus::Published->value)
                ->update(['status' => ThemeStatus::Draft->value]);

            $this->forceFill([
                'status' => ThemeStatus::Published,
                'published_at' => now(),
            ])->save();
        });
    }

    /**
     * Create a draft copy of the theme including files and settings.
     */
    public function duplicate(string $name): Theme
    {
        return DB::transaction(function () use ($name): Theme {
            $copy = $this->replicate(['status', 'published_at'])->forceFill([
                'name' => $name,
                'status' => ThemeStatus::Draft,
                'published_at' => null,
            ]);
            $copy->save();

            foreach ($this->files as $file) {
                $copy->files()->create($file->only(['path', 'storage_key', 'sha256', 'byte_size']));
            }

            if ($this->settings !== null) {
                $copy->settings()->create(['settings_json' => $this->settings->settings_json]);
            }

            return $copy;
        });
    }
}
