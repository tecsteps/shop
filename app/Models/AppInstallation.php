<?php

namespace App\Models;

use App\Enums\AppInstallationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $store_id
 * @property int $app_id
 * @property array<int, string> $scopes_json
 * @property AppInstallationStatus $status
 * @property \Illuminate\Support\Carbon|null $installed_at
 * @property-read Store $store
 * @property-read App $app
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OauthToken> $oauthTokens
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WebhookSubscription> $webhookSubscriptions
 */
class AppInstallation extends Model
{
    /** @use HasFactory<\Database\Factories\AppInstallationFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'app_id',
        'scopes_json',
        'status',
        'installed_at',
    ];

    /**
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
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<App, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * @return HasMany<OauthToken, $this>
     */
    public function oauthTokens(): HasMany
    {
        return $this->hasMany(OauthToken::class, 'installation_id');
    }

    /**
     * @return HasMany<WebhookSubscription, $this>
     */
    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class);
    }
}
