<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Store extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'handle', 'status', 'default_currency', 'default_locale', 'timezone', 'primary_domain', 'metadata'];

    protected $attributes = [
        'status' => StoreStatus::Active->value,
        'default_currency' => 'USD',
        'default_locale' => 'en',
        'timezone' => 'UTC',
    ];

    protected function casts(): array
    {
        return ['status' => StoreStatus::class, 'metadata' => 'array'];
    }

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
        return $this->belongsToMany(User::class, 'store_users')->using(StoreUser::class)->withPivot('role')->withTimestamps();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(StoreSettings::class);
    }

    public function isActive(): bool
    {
        return $this->status === StoreStatus::Active;
    }
}
