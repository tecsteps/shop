<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use BelongsToStore;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'customer_id',
        'type',
        'session_id',
        'client_event_id',
        'properties_json',
        'occurred_at',
        'payload',
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            if ($event->getAttribute('properties_json') === null && $event->getAttribute('payload') !== null) {
                $event->setAttribute('properties_json', $event->getAttribute('payload'));
            }

            if ($event->getAttribute('payload') === null && $event->getAttribute('properties_json') !== null) {
                $event->setAttribute('payload', $event->getAttribute('properties_json'));
            }

            if ($event->getAttribute('occurred_at') === null) {
                $event->setAttribute('occurred_at', $event->getAttribute('created_at') ?? now());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'properties_json' => 'array',
            'occurred_at' => 'datetime',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
