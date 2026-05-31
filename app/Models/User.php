<?php

namespace App\Models;

use App\Enums\StoreUserRole;
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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'status',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }

    /**
     * The column that holds the hashed password.
     *
     * Stored in `password_hash` rather than the Laravel default `password`,
     * so the auth column name must be overridden for Auth::attempt()/Hash::check().
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * The stores this user has access to, with their pivot role.
     *
     * @return BelongsToMany<Store, $this, StoreUser>
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_users')
            ->using(StoreUser::class)
            ->withPivot('role')
            ->withTimestamps()
            ->as('membership');
    }

    /**
     * Resolve the user's role for a given store, or null if they have none.
     */
    public function roleForStore(Store $store): ?StoreUserRole
    {
        $membership = $this->stores()
            ->withoutGlobalScopes()
            ->wherePivot('store_id', $store->id)
            ->first();

        if ($membership === null) {
            return null;
        }

        $role = $membership->membership->role;

        return $role instanceof StoreUserRole ? $role : StoreUserRole::from((string) $role);
    }

    /**
     * Get the user's initials.
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
