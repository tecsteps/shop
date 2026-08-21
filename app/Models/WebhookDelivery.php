<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = ['webhook_subscription_id', 'subscription_id', 'event', 'event_id', 'payload', 'status', 'attempts', 'attempt_count', 'response_status', 'response_code', 'response_body', 'response_body_snippet', 'delivered_at', 'last_attempt_at', 'next_attempt_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'delivered_at' => 'datetime', 'last_attempt_at' => 'datetime', 'next_attempt_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (WebhookDelivery $delivery): void {
            $delivery->subscription_id ??= $delivery->webhook_subscription_id;
            $delivery->attempt_count ??= $delivery->attempts ?? 0;
            $delivery->event_id ??= (string) str()->uuid();
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'webhook_subscription_id');
    }
}
