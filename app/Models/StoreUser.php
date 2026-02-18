<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $store_id
 * @property int $user_id
 * @property StoreUserRole $role
 */
class StoreUser extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'store_users';

    protected $fillable = [
        'store_id',
        'user_id',
        'role',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => StoreUserRole::class,
            'created_at' => 'datetime',
        ];
    }
}
