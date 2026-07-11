<?php

namespace App\Models;

use App\Enums\AppStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    /** @use HasFactory<\Database\Factories\AppFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['name', 'status'];

    protected $attributes = ['status' => AppStatus::Active->value];

    public function installations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }

    public function oauthClients(): HasMany
    {
        return $this->hasMany(OauthClient::class);
    }

    protected function casts(): array
    {
        return ['status' => AppStatus::class];
    }
}
