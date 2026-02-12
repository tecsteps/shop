<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchSettings extends Model
{
    use HasFactory;

    /** @var string */
    protected $primaryKey = 'store_id';

    /** @var bool */
    public $incrementing = false;

    const CREATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'synonyms_json',
        'stop_words_json',
        'boost_fields_json',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'synonyms_json' => 'array',
            'stop_words_json' => 'array',
            'boost_fields_json' => 'array',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
