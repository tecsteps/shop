<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

/**
 * A logged storefront search query, used for analytics and popular-search
 * suggestions. Store-scoped; tracks only `created_at`.
 */
class SearchQuery extends Model
{
    use BelongsToStore;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'search_queries';

    /**
     * The search_queries table only tracks created_at.
     *
     * @var string|null
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'query',
        'filters_json',
        'results_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
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
