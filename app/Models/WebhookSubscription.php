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

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'app_installation_id',
        'event_type',
        'target_url',
        'secret',
        'status',
        'consecutive_failures',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'consecutive_failures' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AppInstallation, $this>
     */
    public function appInstallation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class);
    }

    /**
     * @return HasMany<WebhookDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }
}
