<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use BelongsToStore;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'query',
        'filters_json',
        'results_count',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array',
            'results_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
