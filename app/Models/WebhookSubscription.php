<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WebhookSubscription extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'app_installation_id', 'event_type', 'target_url', 'signing_secret_encrypted', 'status'];

    protected function casts(): array
    {
        return ['signing_secret_encrypted' => 'encrypted'];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }

    public function latestDelivery(): HasOne
    {
        return $this->hasOne(WebhookDelivery::class, 'subscription_id')->latestOfMany();
    }
}
