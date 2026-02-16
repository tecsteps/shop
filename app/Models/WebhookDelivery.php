<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'event_type',
        'payload_json',
        'response_status',
        'response_body',
        'attempt_count',
        'status',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'response_status' => 'integer',
            'attempt_count' => 'integer',
            'delivered_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }
}
