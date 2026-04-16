<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use BelongsToStore;

    const UPDATED_AT = null;

    protected $fillable = ['store_id', 'query', 'results_count'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
