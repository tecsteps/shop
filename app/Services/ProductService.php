<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Services\Concerns\ChecksOrderReferences;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates product creation, updates, lifecycle transitions, and deletion.
 *
 * Products move through a draft -> active -> archived lifecycle (see the
 * product status state machine in the business-logic spec). This service owns
 * the rules guarding those transitions and the destructive-delete guard that
 * protects order history.
 */
class ProductService
{
    use ChecksOrderReferences;

    public function __construct(
        private readonly HandleGenerator $handles,
        private readonly VariantMatrixService $matrix,
    ) {}

    /**
     * Create a product with its nested options, option values, and variants.
     *
     * When no variants are supplied a single default variant is auto-created so
     * the product is always purchasable. The handle is derived from the title
     * unless one is given explicitly.
     *
     * @param  array{
     *     title: string,
     *     handle?: string,
     *     status?: ProductStatus|string,
     *     description_html?: ?string,
     *     vendor?: ?string,
     *     product_type?: ?string,
     *     tags?: array<int, string>,
     *     options?: list<array{name: string, values: list<string>}>,
     *     variants?: list<array<string, mixed>>,
     * }  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $handle = $data['handle'] ?? $this->handles->generate($data['title'], 'products', $store->id);

            $product = Product::create([
                'store_id' => $store->id,
                'title' => $data['title'],
                'handle' => $handle,
                'status' => $this->statusValue($data['status'] ?? ProductStatus::Draft),
                'description_html' => $data['description_html'] ?? null,
                'vendor' => $data['vendor'] ?? null,
                'product_type' => $data['product_type'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);

            $this->syncOptions($product, $data['options'] ?? []);

            if (! empty($data['variants'])) {
                foreach ($data['variants'] as $index => $variantData) {
                    $this->createVariant($product, $variantData, $index);
                }
            } elseif (empty($data['options'])) {
                $this->createVariant($product, ['is_default' => true], 0);
            } else {
                $this->matrix->rebuildMatrix($product);
            }

            return $product->refresh();
        });
    }

    /**
     * Update a product's scalar attributes.
     *
     * Re-validates the handle for uniqueness when the title or handle changes,
     * excluding the product's own row from the collision check.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            if (array_key_exists('handle', $data) && $data['handle'] !== null) {
                $data['handle'] = $this->handles->generate($data['handle'], 'products', $product->store_id, $product->id);
            } elseif (array_key_exists('title', $data) && ! array_key_exists('handle', $data)) {
                $data['handle'] = $this->handles->generate($data['title'], 'products', $product->store_id, $product->id);
            }

            if (array_key_exists('status', $data)) {
                $data['status'] = $this->statusValue($data['status']);
            }

            $product->fill($data)->save();

            return $product->refresh();
        });
    }

    /**
     * Transition a product to a new lifecycle status, enforcing the state
     * machine's preconditions.
     *
     * @throws InvalidProductTransitionException
     */
    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $current = $product->status;

        if ($current === $newStatus) {
            return;
        }

        match (true) {
            // Publishing requires a non-empty title and a priced variant.
            $newStatus === ProductStatus::Active => $this->guardActivation($product),

            // Reverting to draft is blocked once orders reference the product.
            $newStatus === ProductStatus::Draft => $this->guardRevertToDraft($product),

            // Archiving is always allowed.
            $newStatus === ProductStatus::Archived => null,

            default => throw new InvalidProductTransitionException(
                "Unsupported product transition from {$current->value} to {$newStatus->value}.",
            ),
        };

        $product->status = $newStatus;

        if ($newStatus === ProductStatus::Active && $product->published_at === null) {
            $product->published_at = now();
        }

        $product->save();
    }

    /**
     * Hard-delete a product. Permitted only for drafts with no order references;
     * anything else must be archived to preserve order history.
     *
     * @throws InvalidProductTransitionException
     */
    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft) {
            throw new InvalidProductTransitionException(
                'Only draft products may be deleted; archive products with history instead.',
            );
        }

        if ($this->productHasOrderReferences($product)) {
            throw new InvalidProductTransitionException(
                'Cannot delete a product referenced by existing orders; archive it instead.',
            );
        }

        $product->delete();
    }

    /**
     * Require a non-empty title and at least one priced variant before publish.
     *
     * @throws InvalidProductTransitionException
     */
    private function guardActivation(Product $product): void
    {
        if (trim((string) $product->title) === '') {
            throw new InvalidProductTransitionException('A product must have a title before it can be activated.');
        }

        $hasPricedVariant = $product->variants()->where('price_amount', '>', 0)->exists();

        if (! $hasPricedVariant) {
            throw new InvalidProductTransitionException('A product needs at least one variant with a price before it can be activated.');
        }
    }

    /**
     * Block reverting to draft when order lines reference the product.
     *
     * @throws InvalidProductTransitionException
     */
    private function guardRevertToDraft(Product $product): void
    {
        if ($this->productHasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Cannot revert a product to draft while orders reference it.');
        }
    }

    /**
     * Create the product's options and their values.
     *
     * @param  list<array{name: string, values: list<string>}>  $options
     */
    private function syncOptions(Product $product, array $options): void
    {
        foreach (array_values($options) as $position => $optionData) {
            $option = $product->options()->create([
                'name' => $optionData['name'],
                'position' => $position,
            ]);

            foreach (array_values($optionData['values'] ?? []) as $valuePosition => $value) {
                $option->values()->create([
                    'value' => $value,
                    'position' => $valuePosition,
                ]);
            }
        }
    }

    /**
     * Create a single variant from supplied data, defaulting currency to the
     * product's store currency.
     *
     * @param  array<string, mixed>  $variantData
     */
    private function createVariant(Product $product, array $variantData, int $index): ProductVariant
    {
        return $product->variants()->create(array_merge([
            'price_amount' => 0,
            'currency' => $product->store?->default_currency ?? 'USD',
            'requires_shipping' => true,
            'is_default' => $index === 0,
            'position' => $index,
        ], $variantData));
    }

    /**
     * Normalise a status value (enum or string) to its string representation.
     */
    private function statusValue(ProductStatus|string $status): string
    {
        return $status instanceof ProductStatus ? $status->value : $status;
    }
}
