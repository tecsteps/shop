<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    /**
     * The pivot table associated with the model.
     *
     * @var string
     */
    protected $table = 'store_users';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'user_id',
        'role',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => StoreUserRole::class,
        ];
    }
}
