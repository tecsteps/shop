<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CollectionProduct extends Pivot
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'collection_products';

    protected $fillable = ['collection_id', 'product_id', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
