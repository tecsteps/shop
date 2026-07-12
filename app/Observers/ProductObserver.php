<?php

namespace App\Observers;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Product;
use App\Services\SearchService;

final class ProductObserver
{
    public function __construct(private readonly SearchService $search) {}

    public function created(Product $product): void
    {
        $this->search->syncProduct($product);
        event(new ProductCreated($product));
    }

    public function updated(Product $product): void
    {
        $this->search->syncProduct($product);
        event(new ProductUpdated($product));
    }

    public function deleted(Product $product): void
    {
        $this->search->removeProduct((int) $product->id);
        event(new ProductDeleted($product));
    }
}
