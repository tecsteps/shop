<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    /** @use HasFactory<\Database\Factories\SearchQueryFactory> */
    use BelongsToStore, HasFactory;

    public $timestamps = false;

    protected $fillable = ['store_id', 'query', 'filters_json', 'results_count', 'created_at'];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
