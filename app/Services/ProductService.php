<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use App\Support\HtmlSanitizer;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProductService
{
    public function __construct(private readonly HandleGenerator $handles, private readonly HtmlSanitizer $sanitizer) {}

    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $status = $data['status'] ?? ProductStatus::Draft;
            $status = $status instanceof ProductStatus ? $status : ProductStatus::from($status);
            $product = Product::withoutGlobalScopes()->create([
                'store_id' => $store->getKey(),
                'title' => $data['title'],
                'handle' => $data['handle'] ?? $this->handles->generate($data['title'], 'products', $store->getKey()),
                'description' => $this->sanitizer->sanitize($data['description'] ?? null),
                'description_html' => $this->sanitizer->sanitize($data['description_html'] ?? $data['description'] ?? null),
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
                'status' => $status,
                'published_at' => $status === ProductStatus::Active ? now() : null,
            ]);

            foreach ($data['variants'] ?? [['title' => 'Default', 'price_amount' => 0, 'is_default' => true]] as $position => $variant) {
                $product->variants()->create(array_merge($variant, ['position' => $position, 'is_default' => $variant['is_default'] ?? $position === 0]));
            }

            if ($status === ProductStatus::Active && (trim((string) $product->title) === '' || ! $product->variants()->where('price_amount', '>', 0)->exists())) {
                throw new InvalidProductTransitionException('An active product requires a title and a priced variant.');
            }

            return $product->load('variants');
        });
    }

    public function update(Product $product, array $data): Product
    {
        $updates = array_intersect_key($data, array_flip(['title', 'description', 'description_html', 'vendor', 'product_type', 'tags', 'status', 'published_at']));

        $newStatus = null;

        if (array_key_exists('status', $updates)) {
            $newStatus = $updates['status'] instanceof ProductStatus ? $updates['status'] : ProductStatus::from($updates['status']);
            unset($updates['status']);
        }

        if (array_key_exists('description', $data)) {
            $updates['description'] = $this->sanitizer->sanitize($data['description']);
            $updates['description_html'] = $this->sanitizer->sanitize($data['description']);
        } elseif (array_key_exists('description_html', $data)) {
            $updates['description_html'] = $this->sanitizer->sanitize($data['description_html']);
        }

        $product->update($updates);

        if ($newStatus !== null) {
            $this->transitionStatus($product->refresh(), $newStatus);
        }

        return $product->refresh()->load('variants');
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $from = $product->status instanceof ProductStatus ? $product->status : ProductStatus::from($product->status);

        if ($from === $newStatus) {
            return;
        }

        if ($newStatus === ProductStatus::Active && (! $product->variants()->where('price_amount', '>', 0)->exists() || trim((string) $product->title) === '')) {
            throw new InvalidProductTransitionException('An active product requires a title and a priced variant.');
        }

        if ($newStatus === ProductStatus::Draft && in_array($from, [ProductStatus::Active, ProductStatus::Archived], true) && $product->orders()->exists()) {
            throw new InvalidProductTransitionException('Products with order history cannot be reverted to draft.');
        }

        $product->update(['status' => $newStatus, 'published_at' => $newStatus === ProductStatus::Active ? ($product->published_at ?? now()) : null]);
        ProductStatusChanged::dispatch($product->refresh(), $from, $newStatus);
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft || $product->orders()->exists()) {
            throw new LogicException('Only draft products with no order history can be deleted.');
        }

        $product->delete();
    }
}
