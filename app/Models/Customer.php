<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use BelongsToStore, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'store_id',
        'email',
        'password_hash',
        'name',
        'marketing_opt_in',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
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
            'store_id' => 'integer',
            'password_hash' => 'hashed',
            'marketing_opt_in' => 'boolean',
        ];
    }

    /**
     * The database column uses `password_hash` instead of Laravel's
     * default `password`, so Auth::attempt() needs this override.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }

    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
