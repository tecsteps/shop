<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'marketing_opt_in' => false,
        'status' => 'active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
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
}
