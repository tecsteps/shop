<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    protected $fillable = [
        'subscription_id', 'event_type', 'payload_json', 'response_status', 'response_body', 'attempt', 'status', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'delivered_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }
}
