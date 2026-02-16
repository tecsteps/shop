<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchSettings extends Model
{
    use BelongsToStore;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'synonyms_json',
        'stop_words_json',
    ];

    protected function casts(): array
    {
        return [
            'synonyms_json' => 'array',
            'stop_words_json' => 'array',
        ];
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
