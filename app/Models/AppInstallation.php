<?php

namespace App\Models;

use App\Enums\AppInstallationStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppInstallation extends Model
{
    /** @use HasFactory<\Database\Factories\AppInstallationFactory> */
    use BelongsToStore, HasFactory;

    public $timestamps = false;

    /**
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'scopes_json' => '[]',
        'status' => 'active',
    ];

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
}
