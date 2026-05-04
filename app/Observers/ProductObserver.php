<?php

namespace App\Observers;

use App\Enums\ProductStatus;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Product;
use App\Services\SearchService;

class ProductObserver
{
    public function created(Product $product): void
    {
        app(SearchService::class)->syncProduct($product);

        ProductCreated::dispatch($product);
    }

    public function updated(Product $product): void
    {
        app(SearchService::class)->syncProduct($product);

        if ($product->status === ProductStatus::Archived && $product->wasChanged('status')) {
            ProductDeleted::dispatch($product);

            return;
        }

        ProductUpdated::dispatch($product);
    }

    public function deleted(Product $product): void
    {
        app(SearchService::class)->removeProduct($product->getKey());

        ProductDeleted::dispatch($product);
    }

    public function forceDeleted(Product $product): void
    {
        app(SearchService::class)->removeProduct($product->getKey());

        ProductDeleted::dispatch($product);
    }
}
