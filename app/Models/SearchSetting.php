<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;

class SearchSetting extends Model
{
    use BelongsToStore;

    protected $primaryKey = 'store_id';

    public $incrementing = false;

    protected $fillable = ['store_id', 'synonyms', 'stopwords', 'enabled'];

    protected function casts(): array
    {
        return ['synonyms' => 'array', 'stopwords' => 'array', 'enabled' => 'boolean'];
    }
}
