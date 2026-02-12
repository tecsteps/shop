<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
        'created_at',
    ];

    public const UPDATED_AT = null;

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
