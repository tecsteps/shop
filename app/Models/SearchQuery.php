<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use BelongsToStore;

    const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'query',
        'filters_json',
        'results_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'results_count' => 'integer',
        ];
    }
}
