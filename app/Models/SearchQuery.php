<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'query', 'filters_json', 'results_count', 'customer_id'];

    protected function casts(): array
    {
        return ['filters_json' => 'array'];
    }
}
