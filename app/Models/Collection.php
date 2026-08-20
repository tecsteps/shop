<?php

namespace App\Models;

use App\Enums\CollectionStatus;
use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    use BelongsToStore;

    protected $fillable = ['store_id', 'title', 'handle', 'description', 'status', 'image_url'];

    protected function casts(): array
    {
        return ['status' => CollectionStatus::class];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'collection_products')->withPivot('position')->orderBy('collection_products.position');
    }
}
