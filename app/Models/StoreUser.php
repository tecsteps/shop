<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    /** @use HasFactory<\Database\Factories\StoreUserFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_users';

    /**
     * The store_users table only has created_at, no updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * The store_users pivot manages its own timestamps on insert.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Get the attributes that should be cast.
     *
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
