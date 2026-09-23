<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'title', 'handle', 'description_html', 'type', 'status'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')->withPivot('position')->orderByPivot('position');
    }
}
