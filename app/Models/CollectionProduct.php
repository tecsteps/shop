<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CollectionProduct extends Pivot
{
    protected $table = 'collection_products';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'collection_id',
        'product_id',
        'position',
    ];
}
