<?php

namespace App\Models;

use App\Enums\AppInstallationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppInstallation extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'store_id',
        'app_id',
        'scopes_json',
        'status',
        'installed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'store_id' => 'integer',
            'app_id' => 'integer',
            'scopes_json' => 'array',
            'status' => AppInstallationStatus::class,
            'installed_at' => 'datetime',
        ];
    }

    public function oauthTokens(): HasMany
    {
        return $this->hasMany(OauthToken::class, 'installation_id');
    }

    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }
}
