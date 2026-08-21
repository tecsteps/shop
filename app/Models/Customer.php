<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Customer extends Model implements Authenticatable, CanResetPasswordContract
{
    use AuthenticatableTrait, BelongsToStore, CanResetPassword, HasFactory, Notifiable;

    protected $fillable = ['store_id', 'first_name', 'last_name', 'name', 'email', 'password_hash', 'status', 'marketing_opt_in', 'email_verified_at', 'metadata'];

    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'marketing_opt_in' => 'boolean', 'metadata' => 'array', 'password_hash' => 'hashed'];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): ?string
    {
        return $this->password_hash;
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function addresses(): HasMany
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
}
