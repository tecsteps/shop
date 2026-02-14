<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'email',
        'password_hash',
        'password',
        'name',
        'marketing_opt_in',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'marketing_opt_in' => 'boolean',
        ];
    }

    public function getPasswordAttribute(): ?string
    {
        $value = $this->attributes['password_hash'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null) {
            return;
        }

        $this->attributes['password_hash'] = $this->hashPassword($value);
    }

    public function setPasswordHashAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['password_hash'] = null;

            return;
        }

        $this->attributes['password_hash'] = $this->hashPassword($value);
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    private function hashPassword(string $value): string
    {
        return Hash::isHashed($value) ? $value : Hash::make($value);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }
}
