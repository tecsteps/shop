<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Enums\VariantStatus;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Form extends Component
{
    use UsesAdminStore;

    public ?Product $product = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public int $priceAmount = 0;

    public string $sku = '';

    public int $quantityOnHand = 0;

    /**
     * @var array<int, array{name: string, values: array<int, string>}>
     */
    public array $options = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        $this->product = $product?->exists
            ? $product->load('options.values', 'variants.inventoryItem', 'variants.optionValues.option')
            : null;

        if ($this->product === null) {
            Gate::authorize('create', Product::class);

            return;
        }

        Gate::authorize('update', $this->product);

        $variant = $this->product->variants->sortBy('position')->first();

        $this->title = $this->product->title;
        $this->handle = $this->product->handle;
        $this->descriptionHtml = $this->product->description_html ?? '';
        $this->status = $this->product->status->value;
        $this->vendor = $this->product->vendor ?? '';
        $this->productType = $this->product->product_type ?? '';
        $this->tags = implode(', ', $this->product->tags ?? []);
        $this->priceAmount = (int) ($variant?->price_amount ?? 0);
        $this->sku = $variant?->sku ?? '';
        $this->quantityOnHand = (int) ($variant?->inventoryItem?->quantity_on_hand ?? 0);
        $this->options = $this->optionRowsFromProduct($this->product);
        $this->variants = $this->variantRowsFromProduct($this->product);
    }

    public function addOption(): void
    {
        if (count($this->options) >= 3) {
            return;
        }

        $this->options[] = [
            'name' => '',
            'values' => [''],
        ];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
        $this->generateVariants();
    }

    public function addOptionValue(int $optionIndex): void
    {
        if (! isset($this->options[$optionIndex])) {
            return;
        }

        $this->options[$optionIndex]['values'] ??= [];
        $this->options[$optionIndex]['values'][] = '';
    }

    public function removeOptionValue(int $optionIndex, int $valueIndex): void
    {
        if (! isset($this->options[$optionIndex]['values'][$valueIndex])) {
            return;
        }

        unset($this->options[$optionIndex]['values'][$valueIndex]);
        $this->options[$optionIndex]['values'] = array_values($this->options[$optionIndex]['values']);
        $this->generateVariants();
    }

    public function updatedOptions(): void
    {
        $this->generateVariants();
    }

    public function generateVariants(): void
    {
        $options = $this->normalizedOptionRows();

        if ($options === []) {
            $this->variants = [];

            return;
        }

        $existing = collect($this->variants)->keyBy(fn (array $variant): string => (string) ($variant['key'] ?? ''));
        $combinations = $this->cartesian(collect($options)->pluck('values')->all());

        $this->variants = collect($combinations)
            ->map(function (array $combination) use ($existing): array {
                $key = $this->variantKey($combination);
                $current = $existing->get($key, []);

                return [
                    'key' => $key,
                    'optionValues' => $combination,
                    'title' => implode(' / ', $combination),
                    'sku' => (string) ($current['sku'] ?? ''),
                    'barcode' => (string) ($current['barcode'] ?? ''),
                    'priceAmount' => (int) ($current['priceAmount'] ?? $this->priceAmount),
                    'compareAtAmount' => $current['compareAtAmount'] ?? null,
                    'weightG' => $current['weightG'] ?? null,
                    'quantityOnHand' => (int) ($current['quantityOnHand'] ?? $this->quantityOnHand),
                    'requiresShipping' => (bool) ($current['requiresShipping'] ?? true),
                ];
            })
            ->values()
            ->all();
    }

    public function save(ProductService $products): mixed
    {
        $this->generateVariants();

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_map(fn (ProductStatus $status): string => $status->value, ProductStatus::cases()))],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'priceAmount' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255'],
            'quantityOnHand' => ['required', 'integer', 'min:0'],
            'options' => ['array', 'max:3'],
            'options.*.name' => ['nullable', 'string', 'max:255'],
            'options.*.values' => ['array', 'max:50'],
            'options.*.values.*' => ['nullable', 'string', 'max:255'],
            'variants' => ['array', 'max:100'],
            'variants.*.key' => ['nullable', 'string', 'max:500'],
            'variants.*.optionValues' => ['array'],
            'variants.*.optionValues.*' => ['string', 'max:255'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.priceAmount' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.compareAtAmount' => ['nullable', 'integer', 'min:0'],
            'variants.*.weightG' => ['nullable', 'integer', 'min:0'],
            'variants.*.quantityOnHand' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.requiresShipping' => ['boolean'],
        ]);

        $optionError = $this->optionConfigurationError();

        if ($optionError !== null) {
            $this->addError('options', $optionError);

            return null;
        }

        $options = $this->normalizedOptionRows();
        $variantRows = $options === []
            ? [$this->defaultVariantPayload($validated)]
            : $this->variantPayloads($validated['variants'] ?? []);

        if ($this->duplicateSkus($variantRows)) {
            $this->addError('variants', 'Variant SKUs must be unique.');

            return null;
        }

        $payload = [
            'title' => $validated['title'],
            'handle' => $validated['handle'] ?: null,
            'description_html' => $validated['descriptionHtml'],
            'status' => $validated['status'],
            'vendor' => $validated['vendor'] ?: null,
            'product_type' => $validated['productType'] ?: null,
            'tags' => collect(explode(',', $validated['tags'] ?? ''))
                ->map(fn (string $tag): string => trim($tag))
                ->filter()
                ->values()
                ->all(),
            'price_amount' => $validated['priceAmount'],
        ];

        if ($this->product === null) {
            Gate::authorize('create', Product::class);
            $this->product = $products->create($this->currentStore(), $payload);
        } else {
            Gate::authorize('update', $this->product);
            $this->product = $products->update($this->product, $payload);
        }

        $this->product = $products->syncOptionMatrix($this->product, $options, $variantRows);

        $this->notify('Product saved.');

        return $this->redirect(route('admin.products.edit', $this->product), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.products.form', [
            'statuses' => ProductStatus::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => $this->product ? 'Edit product' : 'Create product',
        ]);
    }

    /**
     * @return array<int, array{name: string, values: array<int, string>}>
     */
    private function optionRowsFromProduct(Product $product): array
    {
        return $product->options
            ->sortBy('position')
            ->map(fn ($option): array => [
                'name' => $option->name,
                'values' => $option->values
                    ->sortBy('position')
                    ->pluck('value')
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function variantRowsFromProduct(Product $product): array
    {
        if ($product->options->isEmpty()) {
            return [];
        }

        return $product->variants
            ->sortBy('position')
            ->filter(fn ($variant): bool => $variant->status !== VariantStatus::Archived)
            ->map(function ($variant): array {
                $optionValues = $variant->optionValues
                    ->sortBy(fn ($value): int => (int) ($value->option?->position ?? 0))
                    ->pluck('value')
                    ->values()
                    ->all();

                return [
                    'key' => $this->variantKey($optionValues),
                    'optionValues' => $optionValues,
                    'title' => implode(' / ', $optionValues),
                    'sku' => $variant->sku ?? '',
                    'barcode' => $variant->barcode ?? '',
                    'priceAmount' => (int) $variant->price_amount,
                    'compareAtAmount' => $variant->compare_at_amount,
                    'weightG' => $variant->weight_g,
                    'quantityOnHand' => (int) ($variant->inventoryItem?->quantity_on_hand ?? 0),
                    'requiresShipping' => (bool) $variant->requires_shipping,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, values: array<int, string>}>
     */
    private function normalizedOptionRows(): array
    {
        return collect($this->options)
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
            ->values()
            ->all();
    }

    private function optionConfigurationError(): ?string
    {
        $partiallyFilled = collect($this->options)->contains(function (array $option): bool {
            $hasName = filled(trim((string) ($option['name'] ?? '')));
            $hasValues = collect($option['values'] ?? [])->contains(fn (mixed $value): bool => filled(trim((string) $value)));

            return $hasName !== $hasValues;
        });

        if ($partiallyFilled) {
            return 'Each option needs a name and at least one value.';
        }

        $names = collect($this->normalizedOptionRows())->pluck('name')->map(fn (string $name): string => mb_strtolower($name));

        if ($names->duplicates()->isNotEmpty()) {
            return 'Option names must be unique.';
        }

        return null;
    }

    /**
     * @param  array<int, array<int, string>>  $groups
     * @return array<int, array<int, string>>
     */
    private function cartesian(array $groups): array
    {
        $result = [[]];

        foreach ($groups as $group) {
            $append = [];

            foreach ($result as $combination) {
                foreach ($group as $value) {
                    $append[] = [...$combination, $value];
                }
            }

            $result = $append;
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $optionValues
     */
    private function variantKey(array $optionValues): string
    {
        return collect($optionValues)
            ->map(fn (string $value): string => mb_strtolower(trim($value)))
            ->implode('|');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function defaultVariantPayload(array $validated): array
    {
        return [
            'option_values' => [],
            'sku' => $validated['sku'] ?: null,
            'price_amount' => (int) $validated['priceAmount'],
            'quantity_on_hand' => (int) $validated['quantityOnHand'],
            'requires_shipping' => true,
            'status' => VariantStatus::Active->value,
            'currency' => $this->currentStore()->default_currency,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     * @return array<int, array<string, mixed>>
     */
    private function variantPayloads(array $variants): array
    {
        return collect($variants)
            ->map(fn (array $variant): array => [
                'option_values' => array_values($variant['optionValues'] ?? []),
                'sku' => filled($variant['sku'] ?? null) ? trim((string) $variant['sku']) : null,
                'barcode' => filled($variant['barcode'] ?? null) ? trim((string) $variant['barcode']) : null,
                'price_amount' => (int) ($variant['priceAmount'] ?? 0),
                'compare_at_amount' => $this->nullableInteger($variant['compareAtAmount'] ?? null),
                'weight_g' => $this->nullableInteger($variant['weightG'] ?? null),
                'quantity_on_hand' => (int) ($variant['quantityOnHand'] ?? 0),
                'requires_shipping' => (bool) ($variant['requiresShipping'] ?? true),
                'status' => VariantStatus::Active->value,
                'currency' => $this->currentStore()->default_currency,
            ])
            ->values()
            ->all();
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function duplicateSkus(array $variants): bool
    {
        $skus = collect($variants)
            ->pluck('sku')
            ->filter()
            ->map(fn (string $sku): string => mb_strtolower($sku));

        return $skus->duplicates()->isNotEmpty();
    }
}
