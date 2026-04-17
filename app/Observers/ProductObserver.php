<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

class ProductObserver
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    public function created(Product $product): void
    {
        try {
            $this->searchService->syncProduct($product);
        } catch (\Throwable) {
            // FTS5 table may not be available
        }
    }

    public function updated(Product $product): void
    {
        try {
            $this->searchService->syncProduct($product);
        } catch (\Throwable) {
            // FTS5 table may not be available
        }
    }

    public function deleted(Product $product): void
    {
        try {
            $this->searchService->removeProduct($product->id);
        } catch (\Throwable) {
            // FTS5 table may not be available
        }
    }
}
