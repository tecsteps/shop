<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

class ProductObserver
{
    public function __construct(private SearchService $searchService) {}

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->searchService->syncProduct($product);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $this->searchService->syncProduct($product);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->searchService->removeProduct($product->id);
    }
}
