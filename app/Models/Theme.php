<?php

namespace App\Models;

use App\Enums\ThemeStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Theme extends Model
{
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\ThemeFactory> */
    use HasFactory;

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
     * The files (templates, assets, snippets) that make up this theme.
     *
     * @return HasMany<ThemeFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ThemeFile::class);
    }

    /**
     * The single-row JSON settings bag for this theme.
     *
     * @return HasOne<ThemeSettings, $this>
     */
    public function settings(): HasOne
    {
        return $this->hasOne(ThemeSettings::class);
    }

    /**
     * Whether this theme is the published (live) theme.
     */
    public function isPublished(): bool
    {
        return $this->status === ThemeStatus::Published;
    }

    /**
     * Scope a query to published themes only.
     *
     * @param  Builder<Theme>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ThemeStatus::Published->value);
    }
}
