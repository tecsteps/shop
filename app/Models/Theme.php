<?php

namespace App\Models;

use App\Enums\ThemeStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Theme extends Model
{
    /** @use HasFactory<\Database\Factories\ThemeFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = ['store_id', 'name', 'version', 'status', 'published_at'];

    protected $attributes = ['status' => ThemeStatus::Draft->value];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ThemeFile::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ThemeSettings::class);
    }

    protected function casts(): array
    {
        return ['status' => ThemeStatus::class, 'published_at' => 'datetime'];
    }
}
