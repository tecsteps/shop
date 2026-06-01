<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An issued OAuth2 access/refresh token pair for an {@see AppInstallation}.
 * Token values are stored hashed.
 */
class OauthToken extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_tokens';

    /**
     * This table carries no timestamp columns.
     *
     * @var bool
     */
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

    /**
     * The installation this token authenticates.
     *
     * @return BelongsTo<AppInstallation, $this>
     */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class, 'installation_id');
    }
}
