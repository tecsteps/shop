<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use App\Enums\UserStatus;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'password_hash',
        'password',
        'status',
        'last_login_at',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'status' => UserStatus::class,
        ];
    }

    /**
     * Keep compatibility with code that sets/reads `password`.
     */
    public function getPasswordAttribute(): ?string
    {
        $value = $this->attributes['password_hash'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Ensure any assigned password is stored in `password_hash`.
     */
    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null) {
            return;
        }

        $this->attributes['password_hash'] = $this->hashPassword($value);
    }

    /**
     * Ensure direct writes to password_hash stay hashed.
     */
    public function setPasswordHashAttribute(?string $value): void
    {
        if ($value === null) {
            $this->attributes['password_hash'] = null;

            return;
        }

        $this->attributes['password_hash'] = $this->hashPassword($value);
    }

    /**
     * The auth password column name.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    private function hashPassword(string $value): string
    {
        return Hash::isHashed($value) ? $value : Hash::make($value);
    }

    /**
     * Stores this user can access.
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_users')
            ->using(StoreUser::class)
            ->withPivot('role', 'created_at');
    }

    /**
     * Store membership pivot rows.
     */
    public function storeUsers(): HasMany
    {
        return $this->hasMany(StoreUser::class);
    }

    /**
     * Resolve role for a specific store.
     */
    public function roleForStore(Store $store): ?StoreUserRole
    {
        $value = $this->storeUsers()
            ->where('store_id', $store->id)
            ->value('role');

        if ($value instanceof StoreUserRole) {
            return $value;
        }

        if (is_string($value) || is_int($value)) {
            return StoreUserRole::from($value);
        }

        return null;
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
