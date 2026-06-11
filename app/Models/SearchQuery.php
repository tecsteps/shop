<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    /** @use HasFactory<\Database\Factories\SearchQueryFactory> */
    use BelongsToStore, HasFactory;

    /**
     * Log rows are append-only and never updated.
     */
    public const ?string UPDATED_AT = null;

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
        'created_at',
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
            'created_at' => 'datetime',
        ];
    }
}
