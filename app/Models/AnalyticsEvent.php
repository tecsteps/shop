<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'type', 'session_id', 'customer_id', 'client_event_id', 'payload', 'properties_json', 'occurred_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'properties_json' => 'array', 'occurred_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (AnalyticsEvent $event): void {
            $event->properties_json = $event->payload ?? [];
        });
    }
}
