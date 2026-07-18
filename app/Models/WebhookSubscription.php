<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class WebhookSubscription extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id', 'app_installation_id', 'event_type', 'target_url', 'secret', 'status', 'consecutive_failures',
    ];
}
