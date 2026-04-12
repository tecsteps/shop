<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductService
{
    public function __construct(private VariantMatrixService $variantMatrixService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $title = (string) $data['title'];
            $handle = isset($data['handle']) && $data['handle'] !== ''
                ? HandleGenerator::generate((string) $data['handle'], 'products', $store->id)
                : HandleGenerator::generate($title, 'products', $store->id);

            $product = new Product;
            $product->store_id = $store->id;
            $product->title = $title;
            $product->handle = $handle;
            $product->status = $data['status'] ?? ProductStatus::Draft->value;
            $product->description_html = $data['description_html'] ?? null;
            $product->vendor = $data['vendor'] ?? null;
            $product->product_type = $data['product_type'] ?? null;
            $product->tags = $data['tags'] ?? [];
            $product->published_at = $data['published_at'] ?? null;
            $product->save();

            $options = $data['options'] ?? [];
            $this->syncOptions($product, $options);

            if (! empty($options)) {
                $this->variantMatrixService->rebuildMatrix($product->fresh(['options.values']));
            } else {
                $variantsData = $data['variants'] ?? [];

                if ($variantsData === []) {
                    $product->variants()->create([
                        'price_amount' => 0,
                        'currency' => $store->default_currency ?? 'USD',
                        'is_default' => true,
                        'position' => 0,
                        'status' => 'active',
                    ]);
                } else {
                    foreach ($variantsData as $index => $variantData) {
                        $product->variants()->create(array_merge([
                            'price_amount' => 0,
                            'currency' => $store->default_currency ?? 'USD',
                            'is_default' => $index === 0,
                            'position' => $index,
                            'status' => 'active',
                        ], $variantData));
                    }
                }
            }

            return $product->fresh(['variants', 'options.values']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            if (isset($data['title'])) {
                $product->title = (string) $data['title'];
            }

            if (isset($data['handle']) && $data['handle'] !== $product->handle) {
                $product->handle = HandleGenerator::generate(
                    (string) $data['handle'],
                    'products',
                    (int) $product->store_id,
                    $product->id
                );
            }

            foreach (['description_html', 'vendor', 'product_type', 'tags', 'published_at'] as $field) {
                if (array_key_exists($field, $data)) {
                    $product->{$field} = $data[$field];
                }
            }

            $product->save();

            if (array_key_exists('options', $data)) {
                $this->syncOptions($product, $data['options']);
                $this->variantMatrixService->rebuildMatrix($product->fresh(['options.values']));
            }

            return $product->fresh(['variants', 'options.values']);
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $current = $product->status instanceof ProductStatus
            ? $product->status
            : ProductStatus::from((string) $product->status);

        $allowed = match ($current) {
            ProductStatus::Draft => [ProductStatus::Active],
            ProductStatus::Active => [ProductStatus::Archived],
            ProductStatus::Archived => [ProductStatus::Active],
        };

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException(
                "Cannot transition product from {$current->value} to {$newStatus->value}"
            );
        }

        $product->status = $newStatus;
        $product->save();
    }

    public function delete(Product $product): void
    {
        $status = $product->status instanceof ProductStatus
            ? $product->status
            : ProductStatus::from((string) $product->status);

        if ($status !== ProductStatus::Draft) {
            throw new InvalidArgumentException('Only draft products can be deleted.');
        }

        // Phase 5: also block deletion when order_lines reference this product.
        $product->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(Product $product, array $options): void
    {
        $existingOptions = $product->options()->with('values')->get()->keyBy('name');

        $product->options()->update(['position' => DB::raw('position + 1000')]);

        foreach ($existingOptions as $option) {
            $option->values()->update(['position' => DB::raw('position + 1000')]);
        }

        $keptOptionIds = [];

        foreach ($options as $optionIndex => $optionData) {
            $name = (string) $optionData['name'];
            $position = $optionData['position'] ?? $optionIndex;

            if ($existingOptions->has($name)) {
                $option = $existingOptions->get($name);
                $option->position = $position;
                $option->save();
            } else {
                $option = $product->options()->create([
                    'name' => $name,
                    'position' => $position,
                ]);
            }

            $keptOptionIds[] = $option->id;
            $existingValues = $option->values()->get()->keyBy('value');
            $keptValueIds = [];

            foreach ($optionData['values'] ?? [] as $valueIndex => $value) {
                if ($existingValues->has($value)) {
                    $optionValue = $existingValues->get($value);
                    $optionValue->position = $valueIndex;
                    $optionValue->save();
                } else {
                    $optionValue = $option->values()->create([
                        'value' => $value,
                        'position' => $valueIndex,
                    ]);
                }

                $keptValueIds[] = $optionValue->id;
            }

            $option->values()->whereNotIn('id', $keptValueIds)->delete();
        }

        $product->options()->whereNotIn('id', $keptOptionIds)->delete();
    }
}
