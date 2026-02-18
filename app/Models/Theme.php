<?php

namespace App\Models;

use App\Enums\ThemeStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $store_id
 * @property string $name
 * @property string|null $version
 * @property ThemeStatus $status
 * @property \Illuminate\Support\Carbon|null $published_at
 */
class Theme extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'version',
        'status',
        'published_at',
    ];

    /**
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
     * @return HasMany<ThemeFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ThemeFile::class);
    }

    /**
     * @return HasOne<ThemeSettings, $this>
     */
    public function settings(): HasOne
    {
        return $this->hasOne(ThemeSettings::class);
    }
}
