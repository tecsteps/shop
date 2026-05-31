<?php

namespace App\Models;

use App\Enums\AppInstallationStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An installation of an {@see App} on a specific store, with granted scopes.
 * Store-scoped; tracks no Laravel timestamps (uses `installed_at`).
 */
class AppInstallation extends Model
{
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\AppInstallationFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'app_installations';

    /**
     * This table tracks only `installed_at`, not Laravel timestamps.
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
        'store_id',
        'app_id',
        'scopes_json',
        'status',
        'installed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes_json' => 'array',
            'status' => AppInstallationStatus::class,
            'installed_at' => 'datetime',
        ];
    }

    /**
     * The app that was installed.
     *
     * @return BelongsTo<App, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * The OAuth tokens issued to this installation.
     *
     * @return HasMany<OauthToken, $this>
     */
    public function oauthTokens(): HasMany
    {
        return $this->hasMany(OauthToken::class, 'installation_id');
    }

    /**
     * The webhook subscriptions owned by this installation.
     *
     * @return HasMany<WebhookSubscription, $this>
     */
    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class, 'app_installation_id');
    }
}
