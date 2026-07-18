<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'store_users';

    protected $attributes = [
        'role' => 'staff',
    ];

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

    protected static function booted(): void
    {
        static::creating(function (StoreUser $storeUser): void {
            if ($storeUser->created_at === null) {
                $storeUser->created_at = now();
            }
        });
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
