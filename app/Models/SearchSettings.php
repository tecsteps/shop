<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchSettings extends Model
{
    /** @use HasFactory<\Database\Factories\SearchSettingsFactory> */
    use BelongsToStore, HasFactory;

    public const CREATED_AT = null;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    protected $fillable = ['store_id', 'synonyms_json', 'stop_words_json'];

    protected $attributes = ['synonyms_json' => '[]', 'stop_words_json' => '[]'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    protected function casts(): array
    {
        return ['synonyms_json' => 'array', 'stop_words_json' => 'array', 'updated_at' => 'datetime'];
    }
}
