<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    protected $table = 'store_users';

    /** @var bool */
    public $incrementing = false;

    /** @var bool */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'user_id',
        'role',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => StoreUserRole::class,
        ];
    }
}
