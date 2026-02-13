<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

class ProductObserver
{
    public function __construct(
        private SearchService $searchService,
    ) {}

    public function created(Product $product): void
    {
        $this->searchService->syncProduct($product);
    }

    public function updated(Product $product): void
    {
        $this->searchService->syncProduct($product);
    }

    public function deleted(Product $product): void
    {
        $this->searchService->removeProduct($product->id);
    }
}
