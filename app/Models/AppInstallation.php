<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppInstallation extends Model
{
    /** @use HasFactory<\Database\Factories\AppInstallationFactory> */
    use BelongsToStore, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'app_id',
        'scopes_json',
        'status',
        'installed_at',
        'uninstalled_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'scopes_json' => 'array',
            'installed_at' => 'datetime',
            'uninstalled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<App, $this>
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    /**
     * @return HasMany<WebhookSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class);
    }
}
