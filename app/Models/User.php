<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_hash',
        'status',
        'last_login_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'password_hash',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    /**
     * Map writes to `password` to the `password_hash` column.
     * This ensures compatibility with Fortify and Laravel's PasswordBroker.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if ($key === 'password') {
            $key = 'password_hash';
        }

        parent::setAttribute((string) $key, $value);

        return $this;
    }

    /**
     * @return BelongsToMany<Store, $this, StoreUser, 'pivot'>
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_users')
            ->using(StoreUser::class)
            ->withPivot('role');
    }

    public function roleForStore(Store $store): ?StoreUserRole
    {
        /** @var (Store&object{pivot: StoreUser})|null $storeWithPivot */
        $storeWithPivot = $this->stores()
            ->where('stores.id', $store->id)
            ->first();

        if (! $storeWithPivot) {
            return null;
        }

        /** @var StoreUserRole|string $role */
        $role = $storeWithPivot->pivot->role;

        if ($role instanceof StoreUserRole) {
            return $role;
        }

        return StoreUserRole::tryFrom($role);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word): string => Str::substr($word, 0, 1))
            ->implode('');
    }
}
