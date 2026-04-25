<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Auth\Authenticatable;

class Customer extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use BelongsToStore;
    use HasFactory;

    protected $fillable = ['store_id', 'email', 'name', 'password_hash', 'accepts_marketing', 'last_login_at'];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return [
            'accepts_marketing' => 'boolean',
            'last_login_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password_hash'] = $value;
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}

