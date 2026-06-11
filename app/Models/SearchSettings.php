<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchSettings extends Model
{
    /** @use HasFactory<\Database\Factories\SearchSettingsFactory> */
    use HasFactory;

    /**
     * The table only carries an updated_at timestamp.
     */
    public const ?string CREATED_AT = null;

    /**
     * The table is keyed by store_id (one-to-one with stores).
     */
    protected $table = 'search_settings';

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'store_id',
        'synonyms_json',
        'stop_words_json',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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

    /**
     * Synonym groups, each a list of equivalent terms.
     *
     * @return list<list<string>>
     */
    public function synonymGroups(): array
    {
        return $this->synonyms_json ?? [];
    }

    /**
     * Words excluded from search queries.
     *
     * @return list<string>
     */
    public function stopWords(): array
    {
        return $this->stop_words_json ?? [];
    }
}
