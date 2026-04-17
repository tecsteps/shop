<?php

namespace App\Models;

use App\Enums\AnalyticsEventType;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use BelongsToStore;

    const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'type',
        'session_id',
        'customer_id',
        'properties_json',
        'client_event_id',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AnalyticsEventType::class,
            'properties_json' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
