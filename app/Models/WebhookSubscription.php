<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookSubscriptionFactory> */
    use BelongsToStore, HasFactory;

    protected $fillable = [
        'store_id',
        'app_installation_id',
        'target_url',
        'secret',
        'event_types_json',
        'status',
        'consecutive_failures',
    ];

    protected function casts(): array
    {
        return [
            'event_types_json' => 'array',
            'consecutive_failures' => 'integer',
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<AppInstallation, $this> */
    public function appInstallation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class);
    }

    /** @return HasMany<WebhookDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }
}
