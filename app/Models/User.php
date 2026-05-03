<?php

namespace App\Models;

use App\Enums\StoreUserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'status',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * @return BelongsToMany<Store, $this>
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_users')
            ->using(StoreUser::class)
            ->withPivot('role');
    }

    public function roleForStore(Store $store): ?StoreUserRole
    {
        return $this->roleForStoreId($store->getKey());
    }

    public function roleForStoreId(int $storeId): ?StoreUserRole
    {
        $role = $this->stores()
            ->whereKey($storeId)
            ->first()
            ?->pivot
            ?->role;

        if ($role instanceof StoreUserRole) {
            return $role;
        }

        return is_string($role) ? StoreUserRole::tryFrom($role) : null;
    }

    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password_hash'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
    }

    public function getPasswordAttribute(): ?string
    {
        return $this->password_hash;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
