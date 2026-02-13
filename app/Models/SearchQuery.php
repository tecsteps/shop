<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    /** @use HasFactory<\Database\Factories\SearchQueryFactory> */
    use BelongsToStore, HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'query',
        'results_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
        ];
    }
}
