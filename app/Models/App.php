<?php

namespace App\Models;

use App\Enums\AppStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
        'created_at',
    ];

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
