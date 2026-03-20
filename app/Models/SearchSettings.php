<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchSettings extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'store_id';

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

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
