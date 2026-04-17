<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    protected $table = 'store_users';

    public $incrementing = false;

    public $timestamps = false;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            if (! isset($pivot->attributes['created_at'])) {
                $pivot->setAttribute('created_at', $pivot->freshTimestamp());
            }
        });
    }

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
