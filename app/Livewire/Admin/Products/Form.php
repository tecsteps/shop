<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\MediaService;
use App\Services\ProductService;
use App\Services\VariantMatrixService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Shared product form used by both the create and edit routes (roadmap step
 * 7.3 shared-form pattern): create mode when no product id is bound, edit
 * mode otherwise.
 */
#[Layout('layouts::admin')]
class Form extends Component
{
    use AuthorizesRequests, SendsToasts, WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public string $handle = '';

    public ?string $publishedAt = null;

    /** @var list<int> */
    public array $collectionIds = [];

    /**
     * Option rows: name plus comma-separated values (spec 03 section 4).
     *
     * @var list<array{name: string, values: string}>
     */
    public array $options = [];

    /**
     * Variant matrix rows keyed by their option value combination.
     *
     * @var list<array{key: string, label: string, sku: string, barcode: string, price: string, compareAtPrice: string, weight: string, quantity: int|string, requiresShipping: bool}>
     */
    public array $variants = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newMedia = [];

    /**
     * Uploads held until save in create mode (no product exists yet).
     *
     * @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile>
     */
    public array $pendingMedia = [];

    public function mount(?int $productId = null): void
    {
        if ($productId !== null) {
            $this->product = Product::query()
                ->with(['options.values', 'variants.optionValues', 'variants.inventoryItem', 'collections'])
                ->findOrFail($productId);

            $this->authorize('view', $this->product);
            $this->fillFromProduct();

            return;
        }

        $this->authorize('create', Product::class);
        $this->generateVariants();
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
     * Regenerate the variant matrix preview from the current options,
     * preserving values already entered for combinations that still exist.
     */
    public function generateVariants(): void
    {
        $existingByKey = collect($this->variants)->keyBy('key');
        $optionSets = array_column($this->parsedOptions(), 'values');

        $combinations = $optionSets === [] ? [[]] : $this->cartesianProduct($optionSets);

        $this->variants = array_map(function (array $combination) use ($existingByKey): array {
            $key = $this->combinationKey($combination);

            /** @var array{key: string, label: string, sku: string, barcode: string, price: string, compareAtPrice: string, weight: string, quantity: int|string, requiresShipping: bool} $row */
            $row = $existingByKey->get($key, [
                'key' => $key,
                'label' => $combination === [] ? __('Default') : implode(' / ', $combination),
                'sku' => '',
                'barcode' => '',
                'price' => '0.00',
                'compareAtPrice' => '',
                'weight' => '',
                'quantity' => 0,
                'requiresShipping' => true,
            ]);

            $row['label'] = $combination === [] ? __('Default') : implode(' / ', $combination);

            return $row;
        }, $combinations);
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'options.')) {
            $this->generateVariants();
        }
    }

    public function updatedNewMedia(): void
    {
        $this->validate([
            'newMedia.*' => ['image', 'max:5120'],
        ]);

        if ($this->isEditing) {
            $mediaService = app(MediaService::class);

            foreach ($this->newMedia as $file) {
                $mediaService->attach($this->product, $file);
            }

            $this->newMedia = [];
            $this->toast(__('Media uploaded.'));

            return;
        }

        $this->pendingMedia = [...$this->pendingMedia, ...$this->newMedia];
        $this->newMedia = [];
    }

    public function removePendingMedia(int $index): void
    {
        unset($this->pendingMedia[$index]);
        $this->pendingMedia = array_values($this->pendingMedia);
    }

    public function removeMedia(int $mediaId): void
    {
        $this->authorize('update', $this->product);

        $media = $this->product->media()->findOrFail($mediaId);

        app(MediaService::class)->delete($media);

        $this->toast(__('Media removed.'));
    }

    public function updateMediaAlt(int $mediaId, ?string $altText): void
    {
        $this->authorize('update', $this->product);

        $media = $this->product->media()->findOrFail($mediaId);

        app(MediaService::class)->updateAltText($media, $altText !== null && trim($altText) !== '' ? trim($altText) : null);

        $this->toast(__('Alt text saved.'));
    }

    /**
     * Drag-to-reorder handler (wire:sort): move a media item to a position.
     */
    public function reorderMedia(int $mediaId, int $position): void
    {
        $this->authorize('update', $this->product);

        $orderedIds = $this->product->media()->pluck('id')->all();
        $currentIndex = array_search($mediaId, $orderedIds, true);

        if ($currentIndex === false) {
            return;
        }

        array_splice($orderedIds, $currentIndex, 1);
        array_splice($orderedIds, $position, 0, [$mediaId]);

        app(MediaService::class)->reorder($this->product, $orderedIds);
    }

    public function save(): void
    {
        if ($this->isEditing) {
            $this->authorize('update', $this->product);
        } else {
            $this->authorize('create', Product::class);
        }

        $this->validate();
        $this->assertSkusAreDistinct();

        $isCreating = ! $this->isEditing;

        DB::transaction(function (): void {
            $this->isEditing ? $this->updateProduct() : $this->createProduct();
        });

        if ($isCreating) {
            $this->flashToast(__('Product saved'));
            $this->redirect(route('admin.products.edit', $this->product), navigate: true);

            return;
        }

        $this->product->refresh()->load(['options.values', 'variants.optionValues', 'variants.inventoryItem', 'collections']);
        $this->fillFromProduct();
        $this->toast(__('Product saved'));
    }

    /**
     * Archive the product (spec 03 section 4 delete modal: products are
     * archived to preserve order history).
     */
    public function deleteProduct(): void
    {
        $this->authorize('delete', $this->product);

        try {
            app(ProductService::class)->transitionStatus($this->product, ProductStatus::Archived);
        } catch (InvalidProductTransitionException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return;
        }

        $this->flashToast(__('Product archived'));
        $this->redirect(route('admin.products.index'), navigate: true);
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->product !== null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Collection>
     */
    #[Computed]
    public function availableCollections(): \Illuminate\Database\Eloquent\Collection
    {
        return Collection::query()->orderBy('title')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductMedia>
     */
    #[Computed]
    public function mediaItems(): \Illuminate\Database\Eloquent\Collection
    {
        if (! $this->isEditing) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return $this->product->media()->get();
    }

    public function render(): View
    {
        return view('livewire.admin.products.form')
            ->title($this->isEditing ? $this->product->title : __('Add product'));
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:draft,active,archived'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'handle' => ['nullable', 'string', 'max:255'],
            'publishedAt' => ['nullable', 'date'],
            'collectionIds' => ['array'],
            'collectionIds.*' => ['integer'],
            'options.*.name' => ['nullable', 'string', 'max:255'],
            'options.*.values' => ['nullable', 'string'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compareAtPrice' => ['nullable', 'numeric', 'min:0'],
            'variants.*.weight' => ['nullable', 'integer', 'min:0'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function createProduct(): void
    {
        $store = app('current_store');

        $this->product = app(ProductService::class)->create($store, array_filter([
            'title' => $this->title,
            'handle' => trim($this->handle) !== '' ? trim($this->handle) : null,
            'status' => $this->status,
            'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
            'vendor' => $this->vendor !== '' ? $this->vendor : null,
            'product_type' => $this->productType !== '' ? $this->productType : null,
            'tags' => $this->parsedTags(),
            'options' => $this->parsedOptions(),
        ], fn (mixed $value): bool => $value !== null));

        $this->applyVariantRows();
        $this->syncCollections();
        $this->applyPublishedAt();
        $this->attachPendingMedia();
    }

    protected function updateProduct(): void
    {
        $productService = app(ProductService::class);

        $productService->update($this->product, [
            'title' => $this->title,
            'handle' => trim($this->handle) !== '' ? trim($this->handle) : $this->title,
            'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
            'vendor' => $this->vendor !== '' ? $this->vendor : null,
            'product_type' => $this->productType !== '' ? $this->productType : null,
            'tags' => $this->parsedTags(),
        ]);

        $newStatus = ProductStatus::from($this->status);

        if ($this->product->status !== $newStatus) {
            $productService->transitionStatus($this->product, $newStatus);
        }

        $this->syncOptions();

        app(VariantMatrixService::class)->rebuildMatrix($this->product);

        $this->applyVariantRows();
        $this->syncCollections();
        $this->applyPublishedAt();
    }

    /**
     * Diff-sync the product's options and values against the form state.
     * Values are matched case-insensitively so untouched combinations keep
     * their variants (and inventory) through the matrix rebuild.
     */
    protected function syncOptions(): void
    {
        $existingOptions = $this->product->options()->with('values')->get()->values();
        $formOptions = $this->parsedOptions();

        foreach ($formOptions as $index => $formOption) {
            $option = $existingOptions->get($index);

            if ($option === null) {
                $option = $this->product->options()->create([
                    'name' => $formOption['name'],
                    'position' => $index,
                ]);
            } else {
                $option->update(['name' => $formOption['name'], 'position' => $index]);
            }

            $existingValues = $option->values()->get()->keyBy(fn ($value) => mb_strtolower($value->value));

            foreach ($formOption['values'] as $valueIndex => $value) {
                $existing = $existingValues->pull(mb_strtolower($value));

                if ($existing !== null) {
                    $existing->update(['value' => $value, 'position' => $valueIndex]);
                } else {
                    $option->values()->create(['value' => $value, 'position' => $valueIndex]);
                }
            }

            foreach ($existingValues as $orphanValue) {
                $orphanValue->delete();
            }
        }

        foreach ($existingOptions->slice(count($formOptions)) as $orphanOption) {
            $orphanOption->delete();
        }
    }

    /**
     * Apply the per-variant form rows (price, SKU, barcode, weight, shipping,
     * inventory quantity) to the persisted variants, matched by their option
     * value combination.
     */
    protected function applyVariantRows(): void
    {
        $this->product->refresh()->load(['variants.optionValues', 'variants.inventoryItem']);

        $rowsByKey = collect($this->variants)->keyBy('key');

        foreach ($this->product->variants as $variant) {
            $key = $this->combinationKey($variant->optionValues->pluck('value')->all());
            $row = $rowsByKey->get($key);

            if ($row === null) {
                continue;
            }

            $variant->update([
                'sku' => trim((string) $row['sku']) !== '' ? trim((string) $row['sku']) : null,
                'barcode' => trim((string) $row['barcode']) !== '' ? trim((string) $row['barcode']) : null,
                'price_amount' => $this->toMinorUnits((string) $row['price']),
                'compare_at_amount' => trim((string) $row['compareAtPrice']) !== '' ? $this->toMinorUnits((string) $row['compareAtPrice']) : null,
                'weight_g' => trim((string) $row['weight']) !== '' ? (int) $row['weight'] : null,
                'requires_shipping' => (bool) $row['requiresShipping'],
            ]);

            $variant->inventoryItem?->update(['quantity_on_hand' => (int) $row['quantity']]);
        }
    }

    protected function syncCollections(): void
    {
        $validIds = Collection::query()->whereIn('id', $this->collectionIds)->pluck('id')->all();

        $this->product->collections()->sync($validIds);
    }

    protected function applyPublishedAt(): void
    {
        if (filled($this->publishedAt)) {
            $this->product->forceFill(['published_at' => Carbon::parse($this->publishedAt)])->save();
        }
    }

    protected function attachPendingMedia(): void
    {
        $mediaService = app(MediaService::class);

        foreach ($this->pendingMedia as $file) {
            $mediaService->attach($this->product, $file);
        }

        $this->pendingMedia = [];
    }

    protected function fillFromProduct(): void
    {
        $this->title = $this->product->title;
        $this->descriptionHtml = (string) $this->product->description_html;
        $this->status = $this->product->status->value;
        $this->vendor = (string) $this->product->vendor;
        $this->productType = (string) $this->product->product_type;
        $this->tags = implode(', ', $this->product->tags ?? []);
        $this->handle = $this->product->handle;
        $this->publishedAt = $this->product->published_at?->format('Y-m-d\TH:i');
        $this->collectionIds = $this->product->collections->pluck('id')->all();

        $this->options = $this->product->options
            ->map(fn ($option): array => [
                'name' => $option->name,
                'values' => $option->values->pluck('value')->implode(', '),
            ])
            ->all();

        $this->variants = $this->product->variants
            ->filter(fn (ProductVariant $variant): bool => $variant->status !== \App\Enums\VariantStatus::Archived)
            ->map(fn (ProductVariant $variant): array => [
                'key' => $this->combinationKey($variant->optionValues->pluck('value')->all()),
                'label' => $variant->optionValues->isEmpty() ? __('Default') : $variant->optionValues->pluck('value')->implode(' / '),
                'sku' => (string) $variant->sku,
                'barcode' => (string) $variant->barcode,
                'price' => number_format($variant->price_amount / 100, 2, '.', ''),
                'compareAtPrice' => $variant->compare_at_amount !== null ? number_format($variant->compare_at_amount / 100, 2, '.', '') : '',
                'weight' => $variant->weight_g !== null ? (string) $variant->weight_g : '',
                'quantity' => $variant->inventoryItem?->quantity_on_hand ?? 0,
                'requiresShipping' => $variant->requires_shipping,
            ])
            ->values()
            ->all();
    }

    /**
     * Options parsed into name + value lists, skipping incomplete rows.
     *
     * @return list<array{name: string, values: list<string>}>
     */
    protected function parsedOptions(): array
    {
        $parsed = [];

        foreach ($this->options as $option) {
            $name = trim($option['name'] ?? '');
            $values = collect(explode(',', $option['values'] ?? ''))
                ->map(fn (string $value): string => trim($value))
                ->filter(fn (string $value): bool => $value !== '')
                ->unique(fn (string $value): string => mb_strtolower($value))
                ->values()
                ->all();

            if ($name === '' || $values === []) {
                continue;
            }

            $parsed[] = ['name' => $name, 'values' => $values];
        }

        return $parsed;
    }

    /**
     * @return list<string>
     */
    protected function parsedTags(): array
    {
        return collect(explode(',', $this->tags))
            ->map(fn (string $tag): string => trim($tag))
            ->filter(fn (string $tag): bool => $tag !== '')
            ->values()
            ->all();
    }

    /**
     * Order-independent, case-insensitive key for an option value combination.
     *
     * @param  list<string>  $values
     */
    protected function combinationKey(array $values): string
    {
        $normalized = array_map(fn (string $value): string => mb_strtolower($value), $values);
        sort($normalized);

        return implode('|', $normalized);
    }

    /**
     * @param  list<list<string>>  $sets
     * @return list<list<string>>
     */
    protected function cartesianProduct(array $sets): array
    {
        $combinations = [[]];

        foreach ($sets as $set) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($set as $value) {
                    $next[] = [...$combination, $value];
                }
            }

            $combinations = $next;
        }

        return $combinations;
    }

    /**
     * Reject duplicate SKUs within the submitted variant rows.
     */
    protected function assertSkusAreDistinct(): void
    {
        $skus = collect($this->variants)
            ->map(fn (array $row): string => mb_strtolower(trim((string) $row['sku'])))
            ->filter(fn (string $sku): bool => $sku !== '');

        if ($skus->count() !== $skus->unique()->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'variants' => __('Each variant SKU must be unique.'),
            ]);
        }
    }

    protected function toMinorUnits(string $value): int
    {
        return (int) round((float) str_replace(',', '.', $value) * 100);
    }
}
