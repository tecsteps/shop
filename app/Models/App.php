<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $fillable = ['name', 'handle', 'description', 'scopes_json'];

    protected function casts(): array
    {
        return [
            'scopes_json' => 'array',
        ];
    }
}
