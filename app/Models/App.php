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

    public $timestamps = false;

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
        return $this->hasMany(AppInstallation::class, 'app_id');
    }

    public function oauthClients(): HasMany
    {
        return $this->hasMany(OauthClient::class, 'app_id');
    }
}
