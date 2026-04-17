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

    protected $fillable = [
        'store_id',
        'query',
        'filters_json',
        'results_count',
        'session_id',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'results_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
