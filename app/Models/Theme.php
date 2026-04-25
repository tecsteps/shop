<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'name', 'is_active', 'settings_json'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings_json' => 'array',
        ];
    }
}

