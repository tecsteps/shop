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

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'status',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<StoreDomain, $this> */
    public function domains(): HasMany
    {
        return $this->hasMany(StoreDomain::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_users')
            ->using(StoreUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /** @return HasOne<StoreSettings, $this> */
    public function settings(): HasOne
    {
        return $this->hasOne(StoreSettings::class);
    }

    /** @return HasMany<Theme, $this> */
    public function themes(): HasMany
    {
        return $this->hasMany(Theme::class);
    }

    /** @return HasMany<Page, $this> */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /** @return HasMany<NavigationMenu, $this> */
    public function navigationMenus(): HasMany
    {
        return $this->hasMany(NavigationMenu::class);
    }

    /** @return HasOne<TaxSettings, $this> */
    public function taxSettings(): HasOne
    {
        return $this->hasOne(TaxSettings::class);
    }

    /** @return HasMany<ShippingZone, $this> */
    public function shippingZones(): HasMany
    {
        return $this->hasMany(ShippingZone::class);
    }

    /** @return HasMany<Discount, $this> */
    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<Cart, $this> */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
}
