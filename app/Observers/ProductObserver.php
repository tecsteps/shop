<?php

namespace App\Observers;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\SearchService;
use App\Services\WebhookService;

/**
 * Keeps the products_fts FTS5 index in sync with the products table
 * (spec 05 section 16.2) and dispatches product webhooks (spec 05 section
 * 13.1: product.created / product.updated / product.deleted, where
 * archiving counts as deletion).
 */
class ProductObserver
{
    public function __construct(
        protected SearchService $search,
        protected WebhookService $webhooks,
    ) {}

    public function created(Product $product): void
    {
        $this->search->syncProduct($product);

        $this->webhooks->dispatch($product->store, 'product.created', $this->productPayload($product));
    }

    public function updated(Product $product): void
    {
        $this->search->syncProduct($product);

        $eventType = $product->wasChanged('status') && $product->status === ProductStatus::Archived
            ? 'product.deleted'
            : 'product.updated';

        $this->webhooks->dispatch($product->store, $eventType, $this->productPayload($product));
    }

    public function deleted(Product $product): void
    {
        $this->search->removeProduct($product->getKey());

        $this->webhooks->dispatch($product->store, 'product.deleted', $this->productPayload($product));
    }

    /**
     * @return array<string, mixed>
     */
    protected function productPayload(Product $product): array
    {
        return [
            'id' => $product->getKey(),
            'title' => $product->title,
            'handle' => $product->handle,
            'status' => $product->status?->value,
            'vendor' => $product->vendor,
            'product_type' => $product->product_type,
            'tags' => $product->tags ?? [],
            'published_at' => $product->published_at?->toIso8601String(),
        ];
    }
}
