<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shared create/edit product form.
 *
 * Mirrors the catalog domain model: scalar attributes, an options builder whose
 * values drive an auto-generated variant matrix, collection membership, and
 * media (managed by the nested {@see MediaManager} once the product exists).
 * Persistence flows through {@see ProductService} so handle generation and the
 * status state machine stay consistent with the rest of the system.
 */
#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    use BindsCurrentStore;

    public ?Product $product = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public string $handle = '';

    public ?string $publishedAt = null;

    public bool $showDeleteModal = false;

    /** @var array<int, int> */
    public array $collectionIds = [];

    /**
     * Options builder rows: each `{name, values}` where values is a
     * comma-separated string (e.g. "S, M, L").
     *
     * @var array<int, array{name: string, values: string}>
     */
    public array $options = [];

    /**
     * Generated variant rows keyed by their option-value combination label.
     *
     * @var array<int, array{label: string, sku: string, price: string, compareAtPrice: string, quantity: string, requiresShipping: bool}>
     */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        if ($product !== null && $product->exists) {
            $this->authorize('update', $product);
            $this->product = $product->load(['options.values', 'variants.optionValues', 'collections']);
            $this->fillFromProduct();
        } else {
            $this->authorize('create', Product::class);
            $this->variants = [$this->blankVariant(__('Default'))];
        }
    }

    private function fillFromProduct(): void
    {
        $p = $this->product;

        $this->title = $p->title;
        $this->descriptionHtml = (string) $p->description_html;
        $this->status = $p->status->value;
        $this->vendor = (string) $p->vendor;
        $this->productType = (string) $p->product_type;
        $this->tags = implode(', ', $p->tags ?? []);
        $this->handle = $p->handle;
        $this->publishedAt = $p->published_at?->format('Y-m-d\TH:i');
        $this->collectionIds = $p->collections->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $this->options = $p->options->map(fn ($option): array => [
            'name' => $option->name,
            'values' => $option->values->pluck('value')->implode(', '),
        ])->all();

        $this->variants = $p->variants->map(fn ($variant): array => [
            'label' => $variant->optionValues->pluck('value')->implode(' / ') ?: __('Default'),
            'sku' => (string) $variant->sku,
            'price' => $variant->price_amount !== null ? number_format($variant->price_amount / 100, 2, '.', '') : '',
            'compareAtPrice' => $variant->compare_at_amount !== null ? number_format($variant->compare_at_amount / 100, 2, '.', '') : '',
            'quantity' => (string) ($variant->inventoryItem?->quantity_on_hand ?? 0),
            'requiresShipping' => (bool) $variant->requires_shipping,
        ])->all();

        if ($this->variants === []) {
            $this->variants = [$this->blankVariant(__('Default'))];
        }
    }

    public function getIsEditingProperty(): bool
    {
        return $this->product !== null && $this->product->exists;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Collection>
     */
    public function getAvailableCollectionsProperty()
    {
        return Collection::query()->orderBy('title')->get();
    }

    public function addOption(): void
    {
        $this->options[] = ['name' => '', 'values' => ''];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
        $this->generateVariants();
    }

    /**
     * Regenerate the variant matrix from the current options, preserving
     * pricing/SKU/quantity for combinations that already existed.
     */
    public function generateVariants(): void
    {
        $valueSets = [];

        foreach ($this->options as $option) {
            $values = collect(explode(',', $option['values']))
                ->map(fn (string $v): string => trim($v))
                ->filter()
                ->values()
                ->all();

            if ($values !== []) {
                $valueSets[] = $values;
            }
        }

        if ($valueSets === []) {
            $this->variants = [$this->preserveOrBlank(__('Default'))];

            return;
        }

        $combos = [[]];

        foreach ($valueSets as $set) {
            $next = [];
            foreach ($combos as $combo) {
                foreach ($set as $value) {
                    $next[] = array_merge($combo, [$value]);
                }
            }
            $combos = $next;
        }

        $this->variants = array_map(
            fn (array $combo): array => $this->preserveOrBlank(implode(' / ', $combo)),
            $combos,
        );
    }

    /**
     * @return array{label: string, sku: string, price: string, compareAtPrice: string, quantity: string, requiresShipping: bool}
     */
    private function preserveOrBlank(string $label): array
    {
        foreach ($this->variants as $variant) {
            if (($variant['label'] ?? null) === $label) {
                return $variant;
            }
        }

        return $this->blankVariant($label);
    }

    /**
     * @return array{label: string, sku: string, price: string, compareAtPrice: string, quantity: string, requiresShipping: bool}
     */
    private function blankVariant(string $label): array
    {
        return [
            'label' => $label,
            'sku' => '',
            'price' => '',
            'compareAtPrice' => '',
            'quantity' => '0',
            'requiresShipping' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'handle' => ['nullable', 'string', 'max:255'],
            'variants' => ['array', 'min:1'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.compareAtPrice' => ['nullable', 'numeric', 'min:0'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function save(ProductService $products): mixed
    {
        $this->validate();

        $data = [
            'title' => $this->title,
            'status' => $this->status,
            'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
            'vendor' => $this->vendor !== '' ? $this->vendor : null,
            'product_type' => $this->productType !== '' ? $this->productType : null,
            'tags' => $this->tagList(),
            'handle' => $this->handle !== '' ? $this->handle : null,
        ];

        if ($this->isEditing) {
            $products->update($this->product, $data);
            $product = $this->product;
        } else {
            $product = $products->create(app('current_store'), $data);
        }

        $this->syncOptionsAndVariants($product);
        $this->syncCollections($product);
        $this->syncPublishedAt($product);

        $this->dispatch('toast', type: 'success', message: __('Product saved'));

        if (! $this->isEditing) {
            return $this->redirectRoute('admin.products.edit', $product, navigate: true);
        }

        $this->product = $product->fresh(['options.values', 'variants.optionValues', 'collections']);
        $this->fillFromProduct();

        return null;
    }

    /**
     * Persist the options and reconcile the variant rows against them. Builds
     * the options + values, then maps each generated variant row to its option
     * values and writes price/sku/quantity.
     */
    private function syncOptionsAndVariants(Product $product): void
    {
        $optionData = collect($this->options)
            ->map(fn (array $o): array => [
                'name' => trim($o['name']),
                'values' => collect(explode(',', $o['values']))->map(fn ($v) => trim($v))->filter()->values()->all(),
            ])
            ->filter(fn (array $o): bool => $o['name'] !== '' && $o['values'] !== [])
            ->values();

        // Rebuild options from scratch (admin owns the full option set here).
        $product->options()->delete();

        $optionValueIndex = [];

        foreach ($optionData as $position => $option) {
            $created = $product->options()->create(['name' => $option['name'], 'position' => $position]);

            foreach ($option['values'] as $valuePosition => $value) {
                $valueModel = $created->values()->create(['value' => $value, 'position' => $valuePosition]);
                $optionValueIndex[$value] = $valueModel->id;
            }
        }

        // Reconcile variants: drop existing, recreate from rows.
        $product->variants()->each(fn ($variant) => $variant->delete());

        foreach (array_values($this->variants) as $index => $row) {
            $variant = $product->variants()->create([
                'sku' => $row['sku'] !== '' ? $row['sku'] : null,
                'price_amount' => $this->toCents($row['price']),
                'compare_at_amount' => $row['compareAtPrice'] !== '' ? $this->toCents($row['compareAtPrice']) : null,
                'currency' => $product->store?->default_currency ?? 'USD',
                'requires_shipping' => (bool) $row['requiresShipping'],
                'is_default' => $index === 0,
                'position' => $index,
            ]);

            // Map the row label back to option value ids.
            if ($optionData->isNotEmpty()) {
                $valueIds = collect(explode(' / ', $row['label']))
                    ->map(fn (string $v): ?int => $optionValueIndex[trim($v)] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                if ($valueIds !== []) {
                    $variant->optionValues()->sync($valueIds);
                }
            }

            $variant->inventoryItem?->update(['quantity_on_hand' => (int) $row['quantity']]);
        }
    }

    private function syncCollections(Product $product): void
    {
        $pivot = [];
        foreach (array_values($this->collectionIds) as $position => $id) {
            $pivot[$id] = ['position' => $position];
        }

        $product->collections()->sync($pivot);
    }

    private function syncPublishedAt(Product $product): void
    {
        if ($this->publishedAt !== null && $this->publishedAt !== '') {
            $product->update(['published_at' => $this->publishedAt]);
        }
    }

    public function deleteProduct(ProductService $products): mixed
    {
        $this->authorize('delete', $this->product);

        try {
            $products->transitionStatus($this->product, ProductStatus::Archived);
            $this->dispatch('toast', type: 'success', message: __('Product archived'));
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: __('Could not archive product.'));

            return null;
        }

        return $this->redirectRoute('admin.products.index', navigate: true);
    }

    /**
     * @return array<int, string>
     */
    private function tagList(): array
    {
        return collect(explode(',', $this->tags))
            ->map(fn (string $t): string => trim($t))
            ->filter()
            ->values()
            ->all();
    }

    private function toCents(string|float|int $value): int
    {
        return (int) round(((float) $value) * 100);
    }

    public function updatedTitle(): void
    {
        if (! $this->isEditing && $this->handle === '') {
            $this->handle = Str::slug($this->title);
        }
    }

    public function render()
    {
        return view('livewire.admin.products.form');
    }
}
