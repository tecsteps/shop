<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthToken extends Model
{
    /** @use HasFactory<\Database\Factories\OauthTokenFactory> */
    use HasFactory;

    protected $fillable = [
        'installation_id',
        'access_token',
        'refresh_token',
        'scopes_json',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'scopes_json' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AppInstallation, $this> */
    public function installation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class, 'installation_id');
    }
}
