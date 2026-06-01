<?php

namespace App\Models;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A registered outbound webhook endpoint for a store. Store-scoped; optionally
 * owned by an {@see AppInstallation} (null for store-level hooks).
 *
 * The HMAC signing secret is stored encrypted via the `encrypted` cast. The
 * circuit breaker pauses the subscription after repeated delivery failures.
 */
class WebhookSubscription extends Model
{
    use BelongsToStore;

    /** @use HasFactory<\Database\Factories\WebhookSubscriptionFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'webhook_subscriptions';

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
        'store_id',
        'app_installation_id',
        'event_type',
        'target_url',
        'signing_secret_encrypted',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signing_secret_encrypted' => 'encrypted',
            'status' => WebhookSubscriptionStatus::class,
        ];
    }

    /**
     * The app installation that owns this subscription (null for store-level).
     *
     * @return BelongsTo<AppInstallation, $this>
     */
    public function appInstallation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class, 'app_installation_id');
    }

    /**
     * The delivery attempts logged for this subscription.
     *
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }
}
