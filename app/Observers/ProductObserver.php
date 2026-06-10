<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

/**
 * Keeps the products_fts FTS5 index in sync with the products table
 * (spec 05 section 16.2).
 */
class ProductObserver
{
    public function __construct(protected SearchService $search) {}

    public function created(Product $product): void
    {
        $this->search->syncProduct($product);
    }

    public function updated(Product $product): void
    {
        $this->search->syncProduct($product);
    }

    public function deleted(Product $product): void
    {
        $this->search->removeProduct($product->getKey());
    }
}
