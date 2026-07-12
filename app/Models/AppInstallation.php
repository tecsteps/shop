<?php

namespace App\Models;

use App\Enums\AppInstallationStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppInstallation extends Model
{
    use BelongsToStore, HasFactory;

    public $timestamps = false;

    protected $fillable = ['store_id', 'app_id', 'scopes_json', 'status', 'installed_at'];

    protected function casts(): array
    {
        return [
            'scopes_json' => 'array',
            'status' => AppInstallationStatus::class,
            'installed_at' => 'datetime',
        ];
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function oauthTokens(): HasMany
    {
        return $this->hasMany(OauthToken::class, 'installation_id');
    }

    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class);
    }
}
