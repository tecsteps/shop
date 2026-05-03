<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

class ProductObserver
{
    public function created(Product $product): void
    {
        app(SearchService::class)->syncProduct($product);
    }

    public function updated(Product $product): void
    {
        app(SearchService::class)->syncProduct($product);
    }

    public function deleted(Product $product): void
    {
        app(SearchService::class)->removeProduct($product->id);
    }

    public function forceDeleted(Product $product): void
    {
        app(SearchService::class)->removeProduct($product->id);
    }
}
