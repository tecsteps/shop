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
     * The table only has an updated_at column.
     *
     * @var string|null
     */
    const CREATED_AT = null;

    /**
     * The primary key is the store id (one-to-one with stores).
     *
     * @var string
     */
    protected $primaryKey = 'store_id';

    /**
     * The primary key is not auto-incrementing.
     *
     * @var bool
     */
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
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the store that owns the settings.
     *
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
