<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'type', 'session_id', 'customer_id', 'client_event_id', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }
}
