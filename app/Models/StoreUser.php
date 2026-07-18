<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    public $incrementing = false;

    protected $table = 'store_users';

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

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
