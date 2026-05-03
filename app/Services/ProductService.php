<?php

namespace App\Services;

use App\Actions\SanitizeHtml;
use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Events\ProductStatusChanged;
use App\Exceptions\InvalidProductTransitionException;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductService
{
    public function __construct(
        private readonly HandleGenerator $handleGenerator,
        private readonly VariantMatrixService $variantMatrixService,
        private readonly SanitizeHtml $sanitizeHtml,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Store $store, array $data): Product
    {
        return DB::transaction(function () use ($store, $data): Product {
            if (array_key_exists('description_html', $data)) {
                $data['description_html'] = ($this->sanitizeHtml)($data['description_html']);
            }

            $product = Product::query()->create([
                ...Arr::only($data, [
                    'title',
                    'status',
                    'description_html',
                    'vendor',
                    'product_type',
                    'tags',
                    'published_at',
                ]),
                'store_id' => $store->id,
                'handle' => $data['handle'] ?? $this->handleGenerator->generate($data['title'], (new Product)->getTable(), $store->id),
            ]);

            $this->createOptions($product, $data['options'] ?? []);
            $this->createVariants($product, $data['variants'] ?? []);

            if ($product->variants()->count() === 0) {
                ProductVariant::query()->create([
                    'product_id' => $product->id,
                    'price_amount' => (int) ($data['price_amount'] ?? 0),
                    'currency' => $store->default_currency,
                    'is_default' => true,
                ]);
            }

            if ($product->options()->exists()) {
                $this->variantMatrixService->rebuildMatrix($product);
            }

            return $product->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            if (array_key_exists('description_html', $data)) {
                $data['description_html'] = ($this->sanitizeHtml)($data['description_html']);
            }

            $payload = Arr::only($data, [
                'title',
                'status',
                'description_html',
                'vendor',
                'product_type',
                'tags',
                'published_at',
            ]);

            if (array_key_exists('handle', $data)) {
                $payload['handle'] = $data['handle'] ?: $this->handleGenerator->generate(
                    $data['title'] ?? $product->title,
                    $product->getTable(),
                    $product->store_id,
                    $product->id,
                );
            }

            $product->update($payload);

            return $product->refresh();
        });
    }

    /**
     * @param  array<int, array{name: string, values?: array<int, string>}>  $options
     * @param  array<int, array<string, mixed>>  $variants
     */
    public function syncOptionMatrix(Product $product, array $options, array $variants = []): Product
    {
        return DB::transaction(function () use ($product, $options, $variants): Product {
            $product = Product::withoutGlobalScopes()
                ->with('store')
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $normalizedOptions = $this->normalizedOptions($options);

            $product->options()->delete();

            if ($normalizedOptions === []) {
                $this->syncDefaultVariant($product, $variants[0] ?? []);

                return $product->refresh()->load('options.values', 'variants.inventoryItem', 'variants.optionValues.option');
            }

            $this->createOptions($product, $normalizedOptions);
            $this->variantMatrixService->rebuildMatrix($product->refresh()->load('store'));
            $this->syncGeneratedVariants($product->refresh()->load('store'), $variants);

            return $product->refresh()->load('options.values', 'variants.inventoryItem', 'variants.optionValues.option');
        });
    }

    public function transitionStatus(Product $product, ProductStatus $newStatus): void
    {
        $from = $product->status;

        if ($from === $newStatus) {
            return;
        }

        $this->assertTransitionAllowed($product, $newStatus);

        $product->forceFill([
            'status' => $newStatus,
            'published_at' => $newStatus === ProductStatus::Active && ! $product->published_at
                ? now()
                : $product->published_at,
        ])->save();

        ProductStatusChanged::dispatch($product->refresh(), $from, $newStatus);
    }

    public function delete(Product $product): void
    {
        if ($product->status !== ProductStatus::Draft || $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Only draft products without order references can be deleted.');
        }

        $product->delete();
    }

    private function assertTransitionAllowed(Product $product, ProductStatus $newStatus): void
    {
        if ($newStatus === ProductStatus::Active && ! $this->canActivate($product)) {
            throw new InvalidProductTransitionException('Products need a title and a priced variant before activation.');
        }

        if ($newStatus === ProductStatus::Draft && $this->hasOrderReferences($product)) {
            throw new InvalidProductTransitionException('Products with order references cannot be reverted to draft.');
        }
    }

    private function canActivate(Product $product): bool
    {
        return trim($product->title) !== ''
            && $product->variants()
                ->where('status', VariantStatus::Active->value)
                ->where('price_amount', '>', 0)
                ->exists();
    }

    private function hasOrderReferences(Product $product): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')
            ->whereIn('variant_id', $product->variants()->withoutGlobalScopes()->pluck('id'))
            ->orWhere('product_id', $product->id)
            ->exists();
    }

    /**
     * @param  array<int, array{name: string, values?: array<int, string>}>  $options
     */
    private function createOptions(Product $product, array $options): void
    {
        foreach (array_values($options) as $optionIndex => $optionData) {
            $option = $product->options()->create([
                'name' => $optionData['name'],
                'position' => $optionIndex,
            ]);

            foreach (array_values($optionData['values'] ?? []) as $valueIndex => $value) {
                $option->values()->create([
                    'value' => $value,
                    'position' => $valueIndex,
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function createVariants(Product $product, array $variants): void
    {
        foreach (array_values($variants) as $index => $variantData) {
            $this->assertSkuIsUnique($product, $variantData['sku'] ?? null);

            $product->variants()->create([
                ...Arr::only($variantData, [
                    'sku',
                    'barcode',
                    'price_amount',
                    'compare_at_amount',
                    'currency',
                    'weight_g',
                    'requires_shipping',
                    'is_default',
                    'status',
                ]),
                'position' => $variantData['position'] ?? $index,
                'currency' => $variantData['currency'] ?? $product->store->default_currency,
            ]);
        }
    }

    private function assertSkuIsUnique(Product $product, ?string $sku): void
    {
        if (! $sku) {
            return;
        }

        $exists = ProductVariant::query()
            ->where('sku', $sku)
            ->whereHas('product', fn ($query) => $query->where('store_id', $product->store_id))
            ->exists();

        if ($exists) {
            throw new InvalidProductTransitionException('SKU already exists for this store.');
        }
    }

    /**
     * @param  array<int, array{name?: string, values?: array<int, string>}>  $options
     * @return array<int, array{name: string, values: array<int, string>}>
     */
    private function normalizedOptions(array $options): array
    {
        return collect($options)
            ->map(function (array $option): ?array {
                $name = trim((string) ($option['name'] ?? ''));
                $values = collect($option['values'] ?? [])
                    ->map(fn (mixed $value): string => trim((string) $value))
                    ->filter()
                    ->unique(fn (string $value): string => mb_strtolower($value))
                    ->values()
                    ->all();

                if ($name === '' || $values === []) {
                    return null;
                }

                return [
                    'name' => $name,
                    'values' => $values,
                ];
            })
            ->filter()
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $variantData
     */
    private function syncDefaultVariant(Product $product, array $variantData): void
    {
        $variants = $product->variants()
            ->with('inventoryItem')
            ->orderBy('position')
            ->get()
            ->values();

        $defaultVariant = $variants->first();

        if (! $defaultVariant instanceof ProductVariant) {
            $defaultVariant = $product->variants()->create([
                'price_amount' => 0,
                'currency' => $product->store->default_currency,
                'is_default' => true,
            ]);
        }

        foreach ($variants->skip(1) as $variant) {
            if ($this->variantHasOrderReferences($variant)) {
                $variant->forceFill(['status' => VariantStatus::Archived])->save();

                continue;
            }

            $variant->delete();
        }

        $this->syncVariantFields($product, $defaultVariant->refresh(), $variantData, 0, true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $variantRows
     */
    private function syncGeneratedVariants(Product $product, array $variantRows): void
    {
        $rowsByKey = collect($variantRows)
            ->keyBy(fn (array $row): string => $this->optionValueKey($row['option_values'] ?? []));

        $variants = $product->variants()
            ->with('optionValues.option', 'inventoryItem')
            ->where('status', VariantStatus::Active->value)
            ->orderBy('position')
            ->get()
            ->values();

        $product->variants()
            ->where('status', VariantStatus::Active->value)
            ->update(['is_default' => false]);

        foreach ($variants as $index => $variant) {
            $key = $this->optionValueKey($variant->optionValues
                ->sortBy(fn ($value): int => (int) ($value->option?->position ?? 0))
                ->pluck('value')
                ->all());

            $this->syncVariantFields($product, $variant, $rowsByKey->get($key, []), $index, $index === 0);
        }
    }

    /**
     * @param  array<string, mixed>  $variantData
     */
    private function syncVariantFields(Product $product, ProductVariant $variant, array $variantData, int $position, bool $isDefault): void
    {
        $variant->forceFill([
            'sku' => filled($variantData['sku'] ?? null) ? (string) $variantData['sku'] : null,
            'barcode' => filled($variantData['barcode'] ?? null) ? (string) $variantData['barcode'] : null,
            'price_amount' => (int) ($variantData['price_amount'] ?? $variant->price_amount ?? 0),
            'compare_at_amount' => $this->nullableInteger($variantData['compare_at_amount'] ?? null),
            'currency' => $variantData['currency'] ?? $product->store->default_currency,
            'weight_g' => $this->nullableInteger($variantData['weight_g'] ?? null),
            'requires_shipping' => (bool) ($variantData['requires_shipping'] ?? true),
            'is_default' => $isDefault,
            'position' => $position,
            'status' => $variantData['status'] ?? VariantStatus::Active->value,
        ])->save();

        $variant->inventoryItem()->updateOrCreate(
            ['variant_id' => $variant->id],
            [
                'store_id' => $product->store_id,
                'quantity_on_hand' => (int) ($variantData['quantity_on_hand'] ?? $variant->inventoryItem?->quantity_on_hand ?? 0),
                'policy' => $variantData['inventory_policy'] ?? $variant->inventoryItem?->policy ?? InventoryPolicy::Deny,
            ],
        );
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function optionValueKey(array $values): string
    {
        return collect($values)
            ->map(fn (mixed $value): string => mb_strtolower(trim((string) $value)))
            ->filter()
            ->implode('|');
    }

    private function variantHasOrderReferences(ProductVariant $variant): bool
    {
        if (! Schema::hasTable('order_lines')) {
            return false;
        }

        return DB::table('order_lines')->where('variant_id', $variant->id)->exists();
    }
}
