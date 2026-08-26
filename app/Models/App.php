<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class App extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status'];

    public function installations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }
}
