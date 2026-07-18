<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

class ProductObserver
{
    public function __construct(private SearchService $search) {}

    public function saved(Product $product): void
    {
        $this->search->syncProduct($product);
    }

    public function deleted(Product $product): void
    {
        $this->search->removeProduct($product->id);
    }
}
