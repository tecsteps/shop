<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'email',
        'password',
        'name',
        'marketing_opt_in',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'marketing_opt_in' => 'boolean',
            'password' => 'hashed',
        ];
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
