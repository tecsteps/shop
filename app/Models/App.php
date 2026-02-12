<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'handle',
        'description',
        'icon_url',
        'developer_name',
        'api_scopes_json',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'api_scopes_json' => 'array',
        ];
    }

    public function installations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }
}
