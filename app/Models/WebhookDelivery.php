<?php

namespace App\Models;

use App\Enums\WebhookDeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class);
    }
}
