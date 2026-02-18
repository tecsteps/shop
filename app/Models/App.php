<?php

namespace App\Models;

use App\Enums\AppStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property AppStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AppInstallation> $installations
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OauthClient> $oauthClients
 */
class App extends Model
{
    /** @use HasFactory<\Database\Factories\AppFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'status',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AppStatus::class,
            'created_at' => 'datetime',
        ];
    }

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
