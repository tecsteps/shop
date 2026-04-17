<?php

namespace App\Models;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $store_id
 * @property int|null $app_installation_id
 * @property string $event_type
 * @property string $target_url
 * @property string $signing_secret_encrypted
 * @property WebhookSubscriptionStatus $status
 * @property-read AppInstallation|null $appInstallation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WebhookDelivery> $deliveries
 */
class WebhookSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookSubscriptionFactory> */
    use BelongsToStore, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'app_installation_id',
        'event_type',
        'target_url',
        'signing_secret_encrypted',
        'status',
    ];

    /**
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
     * @return BelongsTo<AppInstallation, $this>
     */
    public function appInstallation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class);
    }

    /**
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }
}
