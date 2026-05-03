<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    /** @use HasFactory<\Database\Factories\AppFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'handle',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * @return HasMany<AppInstallation, $this>
     */
    public function installations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }

    /**
     * @return HasMany<OauthClient, $this>
     */
    public function oauthClients(): HasMany
    {
        return $this->hasMany(OauthClient::class);
    }
}
