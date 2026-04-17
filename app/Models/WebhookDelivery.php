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

    protected $fillable = [
        'subscription_id',
        'event_id',
        'attempt_count',
        'status',
        'last_attempt_at',
        'next_retry_at',
        'response_code',
        'response_body_snippet',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'response_code' => 'integer',
            'last_attempt_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WebhookSubscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }
}
