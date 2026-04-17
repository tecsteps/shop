<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchSettings extends Model
{
    /** @use HasFactory<\Database\Factories\SearchSettingsFactory> */
    use HasFactory;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'store_id',
        'synonyms_json',
        'stop_words_json',
        'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'synonyms_json' => 'array',
            'stop_words_json' => 'array',
            'updated_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
