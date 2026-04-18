<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable implements CanResetPassword
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use BelongsToStore, HasFactory, Notifiable;

    protected $fillable = [
        'store_id',
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'state',
        'accepts_marketing',
        'tags_json',
        'metadata_json',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'accepts_marketing' => 'boolean',
            'tags_json' => 'array',
            'metadata_json' => 'array',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function fullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: $this->email;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }
}
