<?php

namespace App\Models;

use App\Enums\AppStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Platform-wide app registry entry (spec 01 Epic 8). The class shares its
 * short name with the Illuminate App facade, so callers outside this
 * namespace should import it as `use App\Models\App as AppModel` when both
 * are needed in one file.
 */
class App extends Model
{
    /** @use HasFactory<\Database\Factories\AppFactory> */
    use HasFactory;

    /**
     * Explicit table name to keep the model unambiguous despite the
     * collision-prone class name.
     */
    protected $table = 'apps';

    /**
     * The apps table only tracks a creation timestamp.
     */
    public const ?string UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AppStatus::class,
            'created_at' => 'datetime',
        ];
    }

    public function installations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }

    public function oauthClients(): HasMany
    {
        return $this->hasMany(OauthClient::class);
    }
}
