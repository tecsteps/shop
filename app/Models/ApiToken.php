<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    /** @use HasFactory<\Database\Factories\ApiTokenFactory> */
    use BelongsToStore, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'user_id',
        'name',
        'token_hash',
        'abilities_json',
        'last_used_at',
        'revoked_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'abilities_json' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities_json' => 'array',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasAbility(string $ability): bool
    {
        return in_array('*', $this->abilities_json ?? [], true)
            || in_array($ability, $this->abilities_json ?? [], true);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
