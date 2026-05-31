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
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'handle',
        'status',
        'default_currency',
        'default_locale',
        'timezone',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
        ];
    }

    /**
     * The organization that owns this store.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * The domains that route to this store.
     *
     * @return HasMany<StoreDomain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(StoreDomain::class);
    }

    /**
     * The admin/staff users that have access to this store.
     *
     * @return BelongsToMany<User, $this, StoreUser>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_users')
            ->using(StoreUser::class)
            ->withPivot('role')
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * The settings bag for this store.
     *
     * @return HasOne<StoreSettings, $this>
     */
    public function settings(): HasOne
    {
        return $this->hasOne(StoreSettings::class);
    }

    /**
     * The storefront customers that belong to this store.
     *
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Whether the store is suspended (storefront unavailable).
     */
    public function isSuspended(): bool
    {
        return $this->status === StoreStatus::Suspended;
    }
}
