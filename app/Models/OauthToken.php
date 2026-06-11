<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Issued access/refresh tokens for an app installation (spec 01 Epic 8).
 * Token issuance is deferred with the OAuth2 flow; only the storage model
 * exists for now.
 */
class OauthToken extends Model
{
    /** @use HasFactory<\Database\Factories\OauthTokenFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'installation_id',
        'access_token_hash',
        'refresh_token_hash',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class, 'installation_id');
    }
}
