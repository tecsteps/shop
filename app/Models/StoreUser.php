<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    public const UPDATED_AT = null;

    protected $table = 'store_users';

    /**
     * @var list<string>
     */
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
        ];
    }
}
