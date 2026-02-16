<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppModel extends Model
{
    /** @use HasFactory<\Database\Factories\AppModelFactory> */
    use HasFactory;

    protected $table = 'apps';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'developer',
        'icon_url',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    /** @return HasMany<AppInstallation, $this> */
    public function installations(): HasMany
    {
        return $this->hasMany(AppInstallation::class, 'app_id');
    }

    /** @return HasMany<OauthClient, $this> */
    public function oauthClients(): HasMany
    {
        return $this->hasMany(OauthClient::class, 'app_id');
    }
}
