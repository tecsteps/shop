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

    /**
     * @var list<string>
     */
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'attempt_count' => 1,
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_attempt_at' => 'datetime',
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
