<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    public $incrementing = false;

    protected $table = 'store_users';

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'user_id',
        'role',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => StoreUserRole::class,
            'created_at' => 'datetime',
        ];
    }
}
