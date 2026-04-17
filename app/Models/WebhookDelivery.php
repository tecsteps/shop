<?php

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $subscription_id
 * @property string $event_id
 * @property int $attempt_count
 * @property WebhookDeliveryStatus $status
 * @property \Illuminate\Support\Carbon|null $last_attempt_at
 * @property int|null $response_code
 * @property string|null $response_body_snippet
 * @property-read WebhookSubscription $subscription
 */
class WebhookDelivery extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookDeliveryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'subscription_id',
        'event_id',
        'attempt_count',
        'status',
        'last_attempt_at',
        'response_code',
        'response_body_snippet',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WebhookDeliveryStatus::class,
            'attempt_count' => 'integer',
            'response_code' => 'integer',
            'last_attempt_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WebhookSubscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class);
    }
}
