<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthToken extends Model
{
    /** @use HasFactory<\Database\Factories\OauthTokenFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'installation_id',
        'name',
        'access_token_hash',
        'refresh_token_hash',
        'abilities_json',
        'expires_at',
        'last_used_at',
        'created_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'abilities_json' => '[]',
    ];

    /**
     * @return BelongsTo<AppInstallation, $this>
     */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class, 'installation_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities_json' => 'array',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
