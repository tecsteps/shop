<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }

    /**
     * Get the password for authentication (custom column name).
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * Get the stores the user is a member of.
     *
     * @return BelongsToMany<Store, $this, StoreUser>
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_users')
            ->withPivot('role')
            ->using(StoreUser::class);
    }

    /**
     * Get the user's role for a given store, or null when the user has
     * no access to that store.
     */
    public function roleForStore(Store $store): ?StoreUserRole
    {
        /** @var Store|null $membership */
        $membership = $this->stores()->where('stores.id', $store->getKey())->first();

        /** @var StoreUserRole|null $role */
        $role = $membership?->pivot->role;

        return $role;
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
