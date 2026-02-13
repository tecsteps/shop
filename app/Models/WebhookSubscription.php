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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'app_installation_id',
        'target_url',
        'event_types_json',
        'signing_secret_encrypted',
        'is_active',
        'consecutive_failures',
        'paused_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_types_json' => 'array',
            'signing_secret_encrypted' => 'encrypted',
            'is_active' => 'boolean',
            'consecutive_failures' => 'integer',
            'paused_at' => 'datetime',
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
