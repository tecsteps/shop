<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OAuth2 client credentials for an {@see App}. The client secret is stored
 * encrypted via Laravel's `encrypted` cast.
 */
class OauthClient extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'oauth_clients';

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
        'app_id',
        'client_id',
        'client_secret_encrypted',
        'redirect_uris_json',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_secret_encrypted' => 'encrypted',
            'redirect_uris_json' => 'array',
        ];
    }

    /**
     * The app these credentials belong to.
     *
     * @return BelongsTo<App, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
