<?php

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookDeliveryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'subscription_id', 'event_id', 'attempt_count', 'status',
        'last_attempt_at', 'response_code', 'response_body_snippet',
    ];

    protected $attributes = ['attempt_count' => 1, 'status' => WebhookDeliveryStatus::Pending->value];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'status' => WebhookDeliveryStatus::class,
            'last_attempt_at' => 'datetime',
            'response_code' => 'integer',
        ];
    }
}
