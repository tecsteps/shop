<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppInstallation extends Model
{
    /** @use HasFactory<\Database\Factories\AppInstallationFactory> */
    use BelongsToStore, HasFactory;

    /**
     * The table has no timestamp columns.
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
            'installed_at' => 'datetime',
        ];
    }

    /**
     * Get the app this installation belongs to.
     *
     * @return BelongsTo<App, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * Get the webhook subscriptions linked to the installation.
     *
     * @return HasMany<WebhookSubscription, $this>
     */
    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class, 'app_installation_id');
    }

    /**
     * Get the OAuth tokens issued for the installation.
     *
     * @return HasMany<OauthToken, $this>
     */
    public function oauthTokens(): HasMany
    {
        return $this->hasMany(OauthToken::class, 'installation_id');
    }
}
