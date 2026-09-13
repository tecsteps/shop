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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
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
            'id' => 'integer',
            'organization_id' => 'integer',
            'status' => StoreStatus::class,
        ];
    }

    public function storeSetting(): HasOne
    {
        return $this->hasOne(StoreSetting::class);
    }

    public function searchSetting(): HasOne
    {
        return $this->hasOne(SearchSetting::class);
    }

    public function taxSetting(): HasOne
    {
        return $this->hasOne(TaxSetting::class);
    }

    public function storeDomains(): HasMany
    {
        return $this->hasMany(StoreDomain::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function themes(): HasMany
    {
        return $this->hasMany(Theme::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function navigationMenus(): HasMany
    {
        return $this->hasMany(NavigationMenu::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    public function shippingZones(): HasMany
    {
        return $this->hasMany(ShippingZone::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function searchQueries(): HasMany
    {
        return $this->hasMany(SearchQuery::class);
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function appInstallations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_users')->using(StoreUser::class)->withPivot('role');
    }
}
