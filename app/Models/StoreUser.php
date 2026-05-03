<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    public const UPDATED_AT = null;

    protected $table = 'store_users';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'user_id',
        'role',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => StoreUserRole::Staff->value,
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
