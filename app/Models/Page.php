<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'title', 'handle', 'body_html', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }
}

