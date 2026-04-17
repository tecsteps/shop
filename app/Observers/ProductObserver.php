<?php

namespace App\Observers;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\SearchService;

class ProductObserver
{
    public function __construct(protected SearchService $searchService) {}

    public function created(Product $product): void
    {
        if ($product->status === ProductStatus::Active) {
            $this->searchService->syncProduct($product);
        }
    }

    public function updated(Product $product): void
    {
        if ($product->status === ProductStatus::Active) {
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
