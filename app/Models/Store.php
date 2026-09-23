<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Store extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'handle', 'status', 'default_currency', 'default_locale', 'timezone'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(StoreDomain::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_users')->withPivot('role');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(StoreSettings::class);
    }

    public function activeTheme(): HasOne
    {
        return $this->hasOne(Theme::class)->where('is_active', true);
    }

    protected function currency(): Attribute
    {
        return Attribute::get(fn (): string => $this->default_currency);
    }

    protected function email(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->organization?->billing_email);
    }

    protected function themeSettings(): Attribute
    {
        return Attribute::get(fn (): array => $this->activeTheme?->settings?->settings_json ?? $this->settings?->settings_json ?? []);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => data_get($this->theme_settings, 'logo_url'));
    }
}
