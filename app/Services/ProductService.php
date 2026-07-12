<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\InventoryItem;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class ProductService
{
    public function __construct(
        private readonly HandleGenerator $handles,
        private readonly VariantMatrixService $variants,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            $product = Product::withoutGlobalScopes()->create([
                ...Arr::only($data, ['title', 'description_html', 'vendor', 'product_type', 'tags']),
                'store_id' => $store->id,
                'handle' => $data['handle'] ?? $this->handles->generate((string) $data['title'], 'products', $store->id),
                'status' => $data['status'] ?? 'draft',
            ]);

            $this->syncOptions($product, (array) ($data['options'] ?? []));
            if ($product->options()->exists()) {
                $this->variants->rebuildMatrix($product);
            } else {
                $variantData = (array) ($data['variant'] ?? ($data['variants'][0] ?? []));
                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'] ?? null,
                    'barcode' => $variantData['barcode'] ?? null,
                    'price_amount' => (int) ($variantData['price_amount'] ?? 0),
                    'compare_at_amount' => $variantData['compare_at_amount'] ?? null,
                    'currency' => $variantData['currency'] ?? $store->default_currency,
                    'weight_g' => $variantData['weight_g'] ?? null,
                    'requires_shipping' => $variantData['requires_shipping'] ?? true,
                    'is_default' => true,
                    'position' => 0,
                    'status' => 'active',
                ]);
                InventoryItem::withoutGlobalScopes()->updateOrCreate(
                    ['variant_id' => $variant->id],
                    ['store_id' => $store->id, 'quantity_on_hand' => (int) ($variantData['quantity_on_hand'] ?? 0), 'quantity_reserved' => 0, 'policy' => $variantData['policy'] ?? 'deny'],
                );
            }

            return $product->refresh()->load(['variants.inventoryItem', 'options.values']);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $fields = Arr::only($data, ['title', 'description_html', 'vendor', 'product_type', 'tags']);
            if (array_key_exists('handle', $data)) {
                $fields['handle'] = $data['handle'];
            } elseif (array_key_exists('title', $data)) {
                $fields['handle'] = $this->handles->generate((string) $data['title'], 'products', (int) $product->store_id, (int) $product->id);
            }
            $product->update($fields);

            if (array_key_exists('options', $data)) {
                $this->syncOptions($product, (array) $data['options']);
                $this->variants->rebuildMatrix($product);
            }
            if (array_key_exists('status', $data)) {
                $this->transitionStatus($product, $data['status'] instanceof ProductStatus ? $data['status'] : ProductStatus::from((string) $data['status']));
            }

            return $product->refresh()->load(['variants.inventoryItem', 'options.values']);
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $current = $this->value($product->status);
        if ($current === $newStatus->value) {
            return;
        }

        $allowed = [
            'draft' => ['active', 'archived'],
            'active' => ['draft', 'archived'],
            'archived' => ['draft', 'active'],
        ];
        if (! in_array($newStatus->value, $allowed[$current] ?? [], true)) {
            throw new InvalidProductTransitionException("Cannot transition product from {$current} to {$newStatus->value}.");
        }
        if ($newStatus->value === 'active' && (trim((string) $product->title) === '' || ! $product->variants()->where('price_amount', '>', 0)->exists())) {
            throw new InvalidProductTransitionException('An active product needs a title and at least one priced variant.');
        }
        if ($newStatus->value === 'draft' && $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Products with order references cannot return to draft.');
        }

        $product->status = $newStatus;
        if ($newStatus->value === 'active' && $product->published_at === null) {
            $product->published_at = now();
        }
        $product->save();
        event(new ProductStatusChanged($product, $current, $newStatus->value));
    }

    public function delete(Product $product): void
    {
        if ($this->value($product->status) !== 'draft' || $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Only unreferenced draft products may be deleted.');
        }
        $product->delete();
    }

    /** @param list<array<string, mixed>> $options */
    private function syncOptions(Product $product, array $options): void
    {
        if (count($options) > 3) {
            throw new \InvalidArgumentException('A product may have at most three options.');
        }

        $existingOptions = $product->options()->with('values')->get();
        $keptOptionIds = [];

        foreach (array_values($options) as $position => $optionData) {
            $name = trim((string) ($optionData['name'] ?? ''));
            $values = array_values((array) ($optionData['values'] ?? []));
            if ($name === '' || $values === []) {
                throw new \InvalidArgumentException('Every product option needs a name and at least one value.');
            }

            $option = isset($optionData['id'])
                ? $existingOptions->firstWhere('id', (int) $optionData['id'])
                : $existingOptions->first(fn ($candidate) => mb_strtolower($candidate->name) === mb_strtolower($name));
            $option ??= $product->options()->make();
            $option->fill(['name' => $name, 'position' => $position])->save();
            $keptOptionIds[] = $option->id;

            $existingValues = $option->values;
            $keptValueIds = [];
            foreach ($values as $valuePosition => $valueData) {
                $value = trim((string) (is_array($valueData) ? ($valueData['value'] ?? '') : $valueData));
                if ($value === '') {
                    throw new \InvalidArgumentException('Product option values may not be empty.');
                }

                $optionValue = is_array($valueData) && isset($valueData['id'])
                    ? $existingValues->firstWhere('id', (int) $valueData['id'])
                    : $existingValues->first(fn ($candidate) => mb_strtolower($candidate->value) === mb_strtolower($value));
                $optionValue ??= $option->values()->make();
                $optionValue->fill(['value' => $value, 'position' => $valuePosition])->save();
                $keptValueIds[] = $optionValue->id;
            }

            $option->values()->whereNotIn('id', $keptValueIds)->delete();
        }

        $product->options()->whereNotIn('id', $keptOptionIds)->delete();
    }

    private function hasOrderReferences(Product $product): bool
    {
        return OrderLine::query()->where('product_id', $product->id)
            ->orWhereIn('variant_id', ProductVariant::query()->where('product_id', $product->id)->select('id'))
            ->exists();
    }

    private function value(mixed $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
