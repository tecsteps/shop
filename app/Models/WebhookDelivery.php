<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookDeliveryFactory> */
    use HasFactory;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'webhook_subscription_id',
        'event_type',
        'payload_json',
        'response_status',
        'response_body',
        'attempts',
        'next_retry_at',
        'delivered_at',
        'failed_at',
        'created_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'attempts' => 'integer',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'webhook_subscription_id');
    }
}
