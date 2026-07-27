<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

/**
 * Keeps the products_fts index in sync with the products table
 * (spec 05 §16.2). Products are always indexed — visibility rules are
 * applied at query time.
 */
class ProductObserver
{
    public function __construct(private SearchService $search) {}

    /**
     * Index the product after creation.
     */
    public function created(Product $product): void
    {
        $this->search->syncProduct($product);
    }

    /**
     * Re-index the product after an update (delete + insert; FTS5 has no UPDATE).
     */
    public function updated(Product $product): void
    {
        $this->search->syncProduct($product);
    }

    /**
     * Drop the product from the index after deletion.
     */
    public function deleted(Product $product): void
    {
        $this->search->removeProduct($product->id);
    }
}
