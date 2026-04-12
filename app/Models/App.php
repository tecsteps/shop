<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    /** @use HasFactory<\Database\Factories\AppFactory> */
    use HasFactory;

    protected $table = 'apps';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'scopes_json',
        'type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes_json' => 'array',
        ];
    }

    /**
     * @return HasMany<AppInstallation, $this>
     */
    public function installations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }
}
