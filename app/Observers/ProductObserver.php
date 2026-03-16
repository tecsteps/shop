<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

class ProductObserver
{
    public function __construct(protected SearchService $searchService) {}

    public function created(Product $product): void
    {
        if ($product->status->value === 'active') {
            $this->searchService->syncProduct($product);
        }
    }

    public function updated(Product $product): void
    {
        if ($product->status->value === 'active') {
            $this->searchService->syncProduct($product);
        } else {
            $this->searchService->removeProduct($product->id);
        }
    }

    public function deleted(Product $product): void
    {
        $this->searchService->removeProduct($product->id);
    }
}
