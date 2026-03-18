<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'email',
        'password_hash',
        'name',
        'marketing_opt_in',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'marketing_opt_in' => 'boolean',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }
}
