<?php

namespace App\Models;

use App\Enums\AnalyticsEventType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A raw storefront analytics event. Store-scoped; tracks only `created_at`.
 *
 * `client_event_id` is a client-provided idempotency key, unique per store, used
 * to silently drop duplicate batch submissions.
 */
class AnalyticsEvent extends Model
{
    use BelongsToStore;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_events';

    /**
     * The analytics_events table only tracks created_at.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'type',
        'session_id',
        'customer_id',
        'properties_json',
        'client_event_id',
        'occurred_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AnalyticsEventType::class,
            'properties_json' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * The customer who generated this event (null for anonymous visitors).
     *
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
