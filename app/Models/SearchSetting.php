<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class SearchSetting extends Model
{
    use BelongsToStore;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    protected $fillable = ['store_id', 'synonyms', 'stopwords', 'synonyms_json', 'stop_words_json', 'enabled'];

    protected function casts(): array
    {
        return ['synonyms' => 'array', 'stopwords' => 'array', 'synonyms_json' => 'array', 'stop_words_json' => 'array', 'enabled' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (SearchSetting $settings): void {
            $settings->synonyms_json = $settings->synonyms ?? [];
            $settings->stop_words_json = $settings->stopwords ?? [];
        });
    }
}
