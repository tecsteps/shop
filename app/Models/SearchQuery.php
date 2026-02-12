<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use BelongsToStore, HasFactory;

    const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'query_text',
        'results_count',
        'customer_id',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
        ];
    }
}
