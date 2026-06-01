<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

/**
 * Keeps the FTS5 search index in sync with the products table.
 *
 * On create/update the product is upserted (drafts are pruned by the service);
 * on delete it is removed. Registered in {@see \App\Providers\SearchServiceProvider}.
 */
class ProductObserver
{
    public function __construct(private readonly SearchService $search) {}

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
        $this->search->removeProduct($product->id);
    }
}
