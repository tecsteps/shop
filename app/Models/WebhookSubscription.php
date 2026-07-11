<?php

namespace App\Models;

use App\Enums\WebhookSubscriptionStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookSubscription extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookSubscriptionFactory> */
    use BelongsToStore, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'store_id', 'app_installation_id', 'event_type', 'target_url',
        'signing_secret_encrypted', 'status',
    ];

    protected $hidden = ['signing_secret_encrypted'];

    protected $attributes = ['status' => WebhookSubscriptionStatus::Active->value];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function appInstallation(): BelongsTo
    {
        return $this->belongsTo(AppInstallation::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class, 'subscription_id');
    }

    protected function casts(): array
    {
        return [
            'signing_secret_encrypted' => 'encrypted',
            'status' => WebhookSubscriptionStatus::class,
        ];
    }
}
