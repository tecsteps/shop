<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SearchService;

class ProductObserver
{
    public function __construct(private SearchService $search) {}

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        $this->search->syncProduct($product);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        $this->search->syncProduct($product);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->search->removeProduct($product->id);
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        $this->search->syncProduct($product);
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        $this->search->removeProduct($product->id);
    }
}
