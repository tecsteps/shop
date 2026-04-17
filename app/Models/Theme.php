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

    public function files(): HasMany
    {
        return $this->hasMany(ThemeFile::class);
    }

    public function themeSettings(): HasOne
    {
        return $this->hasOne(ThemeSettings::class);
    }
}
