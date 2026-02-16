<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    protected $table = 'store_users';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => StoreUserRole::class,
        ];
    }
}
