<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'store_users';

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
