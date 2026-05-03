<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Customer extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use BelongsToStore, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'email',
        'password',
        'name',
        'marketing_opt_in',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }

    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password_hash'] = $value && Hash::needsRehash($value) ? Hash::make($value) : $value;
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->password_hash;
    }

    /**
     * @return HasMany<Cart, $this>
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * @return HasMany<Checkout, $this>
     */
    public function checkouts(): HasMany
    {
        return $this->hasMany(Checkout::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marketing_opt_in' => 'bool',
        ];
    }
}
