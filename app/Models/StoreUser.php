<?php

namespace App\Models;

use App\Enums\StoreUserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class StoreUser extends Pivot
{
    /** @use HasFactory<\Database\Factories\StoreUserFactory> */
    use HasFactory;

    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'store_users';

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'user_id',
        'role',
        'created_at',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'role' => StoreUserRole::Staff->value,
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => StoreUserRole::class,
            'created_at' => 'datetime',
        ];
    }
}
