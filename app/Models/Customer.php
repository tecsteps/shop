<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use App\Notifications\CustomerResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Customer extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use BelongsToStore, HasFactory, Notifiable;

    protected $authPasswordName = 'password_hash';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'name',
        'email',
        'password',
        'password_hash',
        'marketing_opt_in',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'marketing_opt_in' => false,
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'password_hash',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marketing_opt_in' => 'bool',
            'password_hash' => 'hashed',
        ];
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?string => $attributes['password_hash'] ?? null,
            set: fn (?string $value): array => [
                'password_hash' => $value && ! Hash::isHashed($value) ? Hash::make($value) : $value,
            ],
        );
    }

    /**
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Cart, $this>
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new CustomerResetPasswordNotification($token));
    }
}
