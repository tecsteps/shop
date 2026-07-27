<?php

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookSubscriptionStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookSubscriptionFactory> */
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
     * Get the app installation the subscription belongs to (null for
     * subscriptions created directly by the merchant).
     *
     * @return BelongsTo<AppInstallation, $this>
     */
    public function appInstallation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class, 'app_installation_id');
    }

    /**
     * Get the delivery attempts recorded for the subscription.
     *
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }

    /**
     * Count of consecutive failed deliveries, most recent first. Any
     * non-failed delivery resets the streak (spec 05 §13.4).
     */
    public function consecutiveFailures(): int
    {
        $streak = 0;

        $recentStatuses = $this->deliveries()
            ->orderByDesc('id')
            ->limit(20)
            ->pluck('status');

        foreach ($recentStatuses as $status) {
            if ($status !== WebhookDeliveryStatus::Failed) {
                break;
            }

            $streak++;
        }

        return $streak;
    }
}
