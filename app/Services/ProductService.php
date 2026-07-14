<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Events\ProductDeleted;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Collection as ProductCollection;
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
                if (! empty($data['variants'])) {
                    $this->syncVariants($product, (array) $data['variants']);
                }
            } else {
                $payloads = (array) ($data['variants'] ?? []);
                if ($payloads === []) {
                    $payloads = [(array) ($data['variant'] ?? [])];
                }
                foreach (array_values($payloads) as $position => $variantData) {
                    $this->createVariant($product, (array) $variantData, $position, $position === 0);
                }
            }

            $this->normalizeDefaultVariant($product);

            $this->syncCollections($product, $data);

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
            if (array_key_exists('variants', $data)) {
                $this->syncVariants($product, (array) $data['variants']);
            } elseif (array_key_exists('variant', $data)) {
                $variant = $product->variants()->orderByDesc('is_default')->orderBy('position')->first();
                if ($variant === null) {
                    $this->createVariant($product, (array) $data['variant'], 0, true);
                } else {
                    $this->updateVariant($product, $variant, (array) $data['variant'], 0);
                }
            }
            $this->syncCollections($product, $data);
            if (array_key_exists('status', $data)) {
                $this->transitionStatus($product, $data['status'] instanceof ProductStatus ? $data['status'] : ProductStatus::from((string) $data['status']));
            }
            $this->normalizeDefaultVariant($product);

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
        if ($newStatus === ProductStatus::Archived) {
            event(new ProductDeleted($product));
        }
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
        $combinationCount = 1;
        foreach ($options as $option) {
            $values = collect((array) ($option['values'] ?? []))
                ->map(fn (mixed $value): string => trim((string) (is_array($value) ? ($value['value'] ?? '') : $value)))
                ->filter()
                ->unique(fn (string $value): string => mb_strtolower($value));
            $combinationCount *= max(1, $values->count());
            if ($combinationCount > 100) {
                throw new \InvalidArgumentException('Product options may generate at most 100 variants.');
            }
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
            if (isset($optionData['id']) && $option === null) {
                throw new \InvalidArgumentException('A product option does not belong to this product.');
            }
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
                if (is_array($valueData) && isset($valueData['id']) && $optionValue === null) {
                    throw new \InvalidArgumentException('A product option value does not belong to this product option.');
                }
                $optionValue ??= $option->values()->make();
                $optionValue->fill(['value' => $value, 'position' => $valuePosition])->save();
                $keptValueIds[] = $optionValue->id;
            }

            $option->values()->whereNotIn('id', $keptValueIds)->delete();
        }

        $product->options()->whereNotIn('id', $keptOptionIds)->delete();
    }

    /** @param list<array<string, mixed>> $payloads */
    private function syncVariants(Product $product, array $payloads): void
    {
        $product->load(['options.values', 'variants.optionValues', 'variants.inventoryItem']);
        $used = [];
        $preferredDefaultId = null;

        foreach (array_values($payloads) as $position => $payload) {
            $payload = (array) $payload;
            $valueIds = $this->optionValueIds($product, (array) ($payload['option_values'] ?? []));
            $variant = isset($payload['id'])
                ? $product->variants->firstWhere('id', (int) $payload['id'])
                : null;
            if (isset($payload['id']) && $variant === null) {
                throw new \InvalidArgumentException('A product variant does not belong to this product.');
            }
            if ($variant === null && $valueIds !== []) {
                $key = $this->variantKey($valueIds);
                $variant = $product->variants->first(fn (ProductVariant $candidate): bool => ! in_array($candidate->id, $used, true)
                    && $this->variantKey($candidate->optionValues->modelKeys()) === $key);
            }
            if ($variant === null && $product->options->isEmpty()) {
                $variant = $product->variants->first(fn (ProductVariant $candidate): bool => ! in_array($candidate->id, $used, true));
            }
            $variant ??= $this->createVariant($product, $payload, $position, (bool) ($payload['is_default'] ?? $position === 0));
            $this->updateVariant($product, $variant, $payload, $position);
            if ((bool) ($payload['is_default'] ?? false)) {
                $preferredDefaultId = (int) $variant->id;
            }
            if ($valueIds !== []) {
                $variant->optionValues()->sync($valueIds);
            }
            $used[] = $variant->id;
        }

        foreach ($product->variants as $variant) {
            if (in_array($variant->id, $used, true)) {
                continue;
            }
            if (OrderLine::query()->where('variant_id', $variant->id)->exists()) {
                $variant->update(['status' => 'archived', 'is_default' => false]);
            } else {
                $variant->delete();
            }
        }
        if ($preferredDefaultId !== null) {
            $product->variants()->whereKeyNot($preferredDefaultId)->update(['is_default' => false]);
            $product->variants()->whereKey($preferredDefaultId)->update(['is_default' => true]);
        }
    }

    /** @param array<string, mixed> $data */
    private function createVariant(Product $product, array $data, int $position, bool $default): ProductVariant
    {
        $store = Store::query()->findOrFail($product->store_id);
        $variant = $product->variants()->create([
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'price_amount' => (int) ($data['price_amount'] ?? 0),
            'compare_at_amount' => $data['compare_at_amount'] ?? null,
            'currency' => $data['currency'] ?? $store->default_currency,
            'weight_g' => $data['weight_g'] ?? null,
            'requires_shipping' => $data['requires_shipping'] ?? true,
            'is_default' => $data['is_default'] ?? $default,
            'position' => $data['position'] ?? $position,
            'status' => $data['status'] ?? 'active',
        ]);
        $inventory = (array) ($data['inventory'] ?? []);
        InventoryItem::withoutGlobalScopes()->updateOrCreate(
            ['variant_id' => $variant->id],
            [
                'store_id' => $product->store_id,
                'quantity_on_hand' => (int) ($inventory['quantity_on_hand'] ?? $data['quantity_on_hand'] ?? 0),
                'quantity_reserved' => 0,
                'policy' => $inventory['policy'] ?? $data['policy'] ?? 'deny',
            ],
        );

        return $variant->refresh();
    }

    /** @param array<string, mixed> $data */
    private function updateVariant(Product $product, ProductVariant $variant, array $data, int $position): void
    {
        $variant->update(Arr::only([
            ...$data,
            'position' => $data['position'] ?? $position,
        ], [
            'sku', 'barcode', 'price_amount', 'compare_at_amount', 'currency', 'weight_g',
            'requires_shipping', 'is_default', 'position', 'status',
        ]));
        $inventory = (array) ($data['inventory'] ?? []);
        if ($inventory !== [] || array_key_exists('quantity_on_hand', $data) || array_key_exists('policy', $data)) {
            $item = InventoryItem::withoutGlobalScopes()->where('variant_id', $variant->id)->firstOrFail();
            $item->update([
                'quantity_on_hand' => (int) ($inventory['quantity_on_hand'] ?? $data['quantity_on_hand'] ?? $item->quantity_on_hand),
                'quantity_reserved' => (int) $item->quantity_reserved,
                'policy' => $inventory['policy'] ?? $data['policy'] ?? $item->policy,
            ]);
        }
    }

    /** @param list<array<string, mixed>> $values @return list<int> */
    private function optionValueIds(Product $product, array $values): array
    {
        $ids = [];
        foreach ($values as $value) {
            if (isset($value['id'])) {
                $id = (int) $value['id'];
                if (! $product->options->flatMap->values->contains('id', $id)) {
                    throw new \InvalidArgumentException('A variant option value does not match the product options.');
                }
                $ids[] = $id;

                continue;
            }
            $option = $product->options->first(fn ($candidate) => mb_strtolower($candidate->name) === mb_strtolower((string) ($value['option_name'] ?? '')));
            $optionValue = $option?->values->first(fn ($candidate) => mb_strtolower($candidate->value) === mb_strtolower((string) ($value['value'] ?? '')));
            if ($optionValue === null) {
                throw new \InvalidArgumentException('A variant option value does not match the product options.');
            }
            $ids[] = (int) $optionValue->id;
        }

        sort($ids);

        return $ids;
    }

    /** @param array<int, int|string> $ids */
    private function variantKey(array $ids): string
    {
        sort($ids);

        return implode(':', $ids);
    }

    /** @param array<string, mixed> $data */
    private function syncCollections(Product $product, array $data): void
    {
        if (! array_key_exists('collections', $data)) {
            return;
        }

        $ids = collect(array_values((array) $data['collections']))->map(fn (mixed $id): int => (int) $id)->unique()->values();
        $validIds = ProductCollection::withoutGlobalScopes()
            ->where('store_id', $product->store_id)
            ->whereKey($ids)
            ->pluck('id');
        if ($validIds->count() !== $ids->count()) {
            throw new \InvalidArgumentException('A selected collection does not belong to this store.');
        }

        $product->collections()->sync($ids
            ->mapWithKeys(fn (int $id, int $position): array => [$id => ['position' => $position]])
            ->all());
    }

    private function normalizeDefaultVariant(Product $product): void
    {
        $variants = $product->variants()->reorder()->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('is_default')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
        $default = $variants->first();
        if ($default === null) {
            throw new \InvalidArgumentException('A product must have at least one variant.');
        }

        $product->variants()->whereKeyNot($default->id)->where('is_default', true)->update(['is_default' => false]);
        if (! $default->is_default) {
            $default->update(['is_default' => true]);
        }
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
