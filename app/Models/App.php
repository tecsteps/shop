<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'status', 'scopes'];

    protected function casts(): array
    {
        return ['scopes' => 'array'];
    }

    public function installations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }
}
