<?php

namespace App\Livewire\Admin\Products;

use App\Enums\InventoryPolicy;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Exceptions\InvalidProductTransitionException;
use App\Jobs\ProcessMediaUpload;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public ?string $publishedAt = null;

    /** @var array<int, int|string> */
    public array $collectionIds = [];

    /** @var list<array{name: string, values: string}> */
    public array $options = [];

    /** @var list<array<string, mixed>> */
    public array $variants = [];

    /** @var list<array{id: int, url: string, alt_text: string, position: int}> */
    public array $media = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newMedia = [];

    public bool $confirmingDelete = false;

    public bool $handleManuallyEdited = false;

    public function mount(?Product $product = null): void
    {
        if ($product !== null && $product->exists) {
            $this->authorize('update', $product);

            $this->product = $product;
            $this->loadFromProduct($product);
        } else {
            $this->authorize('create', Product::class);

            $this->variants = [$this->blankVariant()];
        }
    }

    /**
     * Auto-generate the URL handle from the title while the user has not
     * edited it manually (spec 03 §4).
     */
    public function updatedTitle(string $value): void
    {
        if (! $this->isEditing() && ! $this->handleManuallyEdited) {
            $this->handle = Str::slug($value);
        }
    }

    public function updatedHandle(): void
    {
        $this->handleManuallyEdited = true;
    }

    public function updatedNewMedia(): void
    {
        $this->validateOnly('newMedia.*');
    }

    /**
     * Add an option row (maximum of three, spec 03 §4).
     */
    public function addOption(): void
    {
        if (count($this->options) >= 3) {
            return;
        }

        $this->options[] = ['name' => '', 'values' => ''];
    }

    /**
     * Remove an option and regenerate the variant matrix.
     */
    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);

        $this->generateVariants();
    }

    /**
     * Add a value to an option (appended to the comma-separated list).
     */
    public function addOptionValue(int $optionIndex): void
    {
        // Values are edited as a comma-separated list; regeneration happens
        // on change. This action exists for parity with the spec and simply
        // triggers a rebuild.
        $this->generateVariants();
    }

    /**
     * Remove a value from an option's comma-separated list.
     */
    public function removeOptionValue(int $optionIndex, int $valueIndex): void
    {
        $values = array_map('trim', explode(',', $this->options[$optionIndex]['values'] ?? ''));
        unset($values[$valueIndex]);

        $this->options[$optionIndex]['values'] = implode(', ', array_values($values));

        $this->generateVariants();
    }

    /**
     * Generate the variant matrix from the current options, preserving any
     * per-variant data already entered (spec 03 §4).
     */
    public function generateVariants(): void
    {
        $parsed = $this->parsedOptions();

        if ($parsed === []) {
            $default = collect($this->variants)->firstWhere('key', 'default') ?? $this->blankVariant();
            $this->variants = [$default];

            return;
        }

        $previous = collect($this->variants)->keyBy('key');
        $defaultData = $previous->get('default');

        $combinations = $this->cartesian(array_column($parsed, 'values'));

        $this->variants = array_map(function (array $combination) use ($previous, $defaultData): array {
            $key = $this->variantKey($combination);

            $row = $previous->get($key) ?? array_merge($this->blankVariant(), $defaultData !== null ? [
                'sku' => $defaultData['sku'],
                'price' => $defaultData['price'],
                'compareAtPrice' => $defaultData['compareAtPrice'],
                'barcode' => $defaultData['barcode'],
                'weight_g' => $defaultData['weight_g'],
                'quantity' => $defaultData['quantity'],
                'policy' => $defaultData['policy'],
                'requiresShipping' => $defaultData['requiresShipping'],
            ] : []);

            $row['key'] = $key;
            $row['label'] = implode(' / ', $combination);
            $row['optionValues'] = $combination;

            return $row;
        }, $combinations);
    }

    /**
     * Remove an uploaded-but-unsaved file from the pending list.
     */
    public function removeNewMedia(int $index): void
    {
        unset($this->newMedia[$index]);
        $this->newMedia = array_values($this->newMedia);
    }

    /**
     * Delete an existing media item (file cleanup happens on the model).
     */
    public function removeMedia(int $mediaId): void
    {
        abort_if($this->product === null, 404);
        $this->authorize('update', $this->product);

        $this->product->media()->whereKey($mediaId)->firstOrFail()->delete();

        $this->media = array_values(array_filter(
            $this->media,
            fn (array $media): bool => $media['id'] !== $mediaId,
        ));
    }

    /**
     * Move a media item up or down in the grid and persist positions.
     */
    public function moveMedia(int $mediaId, string $direction): void
    {
        $index = array_search($mediaId, array_column($this->media, 'id'), true);

        if ($index === false) {
            return;
        }

        $swap = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($this->media[$swap])) {
            return;
        }

        [$this->media[$index], $this->media[$swap]] = [$this->media[$swap], $this->media[$index]];

        $this->persistMediaOrder();
    }

    /**
     * Persist an explicit ordering of media ids (spec 03 §4 reorderMedia).
     *
     * @param  list<int>  $order
     */
    public function reorderMedia(array $order): void
    {
        abort_if($this->product === null, 404);
        $this->authorize('update', $this->product);

        foreach (array_values($order) as $position => $mediaId) {
            $this->product->media()->whereKey($mediaId)->update(['position' => $position]);
        }

        $this->media = $this->mediaFromProduct($this->product->refresh());
    }

    /**
     * Update the alt text of a media item.
     */
    public function updateMediaAlt(int $mediaId, string $alt): void
    {
        abort_if($this->product === null, 404);
        $this->authorize('update', $this->product);

        $this->product->media()->whereKey($mediaId)->update(['alt_text' => $alt]);

        foreach ($this->media as $index => $media) {
            if ($media['id'] === $mediaId) {
                $this->media[$index]['alt_text'] = $alt;
            }
        }
    }

    /**
     * Archive the product from the edit page (spec 03 §4 delete modal).
     */
    public function deleteProduct(ProductService $products): void
    {
        abort_if($this->product === null, 404);
        $this->authorize('archive', $this->product);

        $this->confirmingDelete = false;

        if ($this->product->status !== ProductStatus::Archived) {
            $products->transitionStatus($this->product, ProductStatus::Archived);
        }

        session()->flash('toast', ['type' => 'success', 'message' => 'Product archived']);

        $this->redirect(route('admin.products.index'));
    }

    /**
     * Validate and save the product graph (spec 03 §4).
     */
    public function save(ProductService $products): void
    {
        $this->normalizeNullableInputs();

        $validated = $this->validate($this->rules());

        /** @var Store $store */
        $store = app('current_store');

        $this->assertSkusAreUnique($store);

        $parsedOptions = $this->parsedOptions();

        $data = [
            'title' => $validated['title'],
            'description_html' => $validated['descriptionHtml'] ?? null,
            'vendor' => $validated['vendor'] ?? null,
            'product_type' => $validated['productType'] ?? null,
            'tags' => array_values(array_filter(array_map('trim', explode(',', $this->tags)))),
            'published_at' => $validated['publishedAt'] ?? null,
            'handle' => $validated['handle'],
            'variants' => $this->variantPayload(),
        ];

        // Only pass options when the product has (or should have) any. For a
        // product without options the service applies the single default
        // variant from the "variants" payload instead.
        if ($parsedOptions !== [] || ($this->isEditing() && $this->product->options()->exists())) {
            $data['options'] = $parsedOptions;
        }

        if ($this->isEditing()) {
            $this->authorize('update', $this->product);

            $product = $products->update($this->product, $data);

            $this->applyStatus($products, $product);
        } else {
            $this->authorize('create', Product::class);

            $product = $products->create($store, array_merge($data, [
                'status' => ProductStatus::from($validated['status']),
            ]));
        }

        $product->collections()->sync(
            collect($this->collectionIds)->mapWithKeys(fn ($id, $index): array => [(int) $id => ['position' => $index]])->all(),
        );

        $this->storeUploadedMedia($product);

        if ($this->isEditing()) {
            $this->persistMediaAltTexts($product);

            $this->product = $product->refresh();
            $this->loadFromProduct($this->product);
            $this->newMedia = [];

            $this->dispatch('toast', type: 'success', message: 'Product saved');
        } else {
            session()->flash('toast', ['type' => 'success', 'message' => 'Product saved']);

            $this->redirect(route('admin.products.edit', $product));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.products.form', [
            'availableCollections' => Collection::query()->orderBy('title')->get(),
        ])->layout('admin.layouts.app')->title($this->isEditing() ? $this->title : 'Add product');
    }

    /**
     * Whether the form is editing an existing product.
     */
    public function isEditing(): bool
    {
        return $this->product !== null && $this->product->exists;
    }

    /**
     * Validation rules (spec 03 §4).
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        /** @var Store $store */
        $store = app('current_store');

        return [
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'handle' => [
                'required', 'string', 'max:255',
                Rule::unique('products', 'handle')
                    ->where('store_id', $store->id)
                    ->ignore($this->product?->id),
            ],
            'publishedAt' => ['nullable', 'date'],
            'options' => ['array', 'max:3'],
            'options.*.name' => ['nullable', 'string', 'max:255'],
            'options.*.values' => ['nullable', 'string'],
            'variants' => ['array', 'min:1'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.compareAtPrice' => ['nullable', 'integer', 'min:0'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.weight_g' => ['nullable', 'integer', 'min:0'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
            'variants.*.requiresShipping' => ['boolean'],
            'variants.*.policy' => ['required', Rule::in(['deny', 'continue'])],
            'collectionIds' => ['array'],
            'collectionIds.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $store->id)],
            'newMedia' => ['array'],
            'newMedia.*' => ['image', 'max:5120'],
        ];
    }

    /**
     * Convert empty-string optional numerics to null so "nullable|integer"
     * validates correctly from Livewire inputs.
     */
    private function normalizeNullableInputs(): void
    {
        $this->publishedAt = $this->publishedAt === '' ? null : $this->publishedAt;

        foreach ($this->variants as $index => $variant) {
            foreach (['compareAtPrice', 'weight_g'] as $field) {
                if (($variant[$field] ?? null) === '') {
                    $this->variants[$index][$field] = null;
                }
            }
        }
    }

    /**
     * Parse the option rows into service payload shape, skipping incomplete
     * rows (no name or no values).
     *
     * @return list<array{name: string, values: list<string>}>
     */
    private function parsedOptions(): array
    {
        $parsed = [];

        foreach ($this->options as $option) {
            $name = trim((string) ($option['name'] ?? ''));
            $values = array_values(array_unique(array_filter(
                array_map('trim', explode(',', (string) ($option['values'] ?? ''))),
                fn (string $value): bool => $value !== '',
            )));

            if ($name === '' || $values === []) {
                continue;
            }

            $parsed[] = ['name' => $name, 'values' => $values];
        }

        return $parsed;
    }

    /**
     * Build the variants payload for the product service.
     *
     * @return list<array<string, mixed>>
     */
    private function variantPayload(): array
    {
        $hasOptions = $this->parsedOptions() !== [];

        return array_map(function (array $variant) use ($hasOptions): array {
            $payload = [
                'id' => $variant['id'] ?? null,
                'sku' => trim((string) ($variant['sku'] ?? '')) !== '' ? trim((string) $variant['sku']) : null,
                'price_amount' => (int) $variant['price'],
                'compare_at_amount' => $variant['compareAtPrice'] !== null && $variant['compareAtPrice'] !== '' ? (int) $variant['compareAtPrice'] : null,
                'barcode' => trim((string) ($variant['barcode'] ?? '')) !== '' ? trim((string) $variant['barcode']) : null,
                'weight_g' => $variant['weight_g'] !== null && $variant['weight_g'] !== '' ? (int) $variant['weight_g'] : null,
                'requires_shipping' => (bool) ($variant['requiresShipping'] ?? true),
                'inventory' => [
                    'quantity_on_hand' => (int) $variant['quantity'],
                    'policy' => $variant['policy'] ?? InventoryPolicy::Deny->value,
                ],
            ];

            if ($hasOptions) {
                $payload['option_values'] = $variant['optionValues'];
            }

            return $payload;
        }, $this->variants);
    }

    /**
     * Apply the requested status on edit via the state machine, surfacing
     * blocked transitions as an error toast (spec 03 §4).
     */
    private function applyStatus(ProductService $products, Product $product): void
    {
        $newStatus = ProductStatus::from($this->status);

        if ($product->status === $newStatus) {
            return;
        }

        try {
            $products->transitionStatus($product, $newStatus);
        } catch (InvalidProductTransitionException $exception) {
            $this->status = $product->status->value;

            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
        }
    }

    /**
     * Ensure entered SKUs are unique within the form and across the store
     * (spec 03 §4: SKU uniqueness errors surfaced).
     */
    private function assertSkusAreUnique(Store $store): void
    {
        $seen = [];

        foreach ($this->variants as $index => $variant) {
            $sku = trim((string) ($variant['sku'] ?? ''));

            if ($sku === '') {
                continue;
            }

            if (in_array($sku, $seen, true)) {
                throw ValidationException::withMessages([
                    "variants.{$index}.sku" => ["The SKU '{$sku}' is entered more than once."],
                ]);
            }

            $seen[] = $sku;

            $query = DB::table('product_variants')
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->where('products.store_id', $store->id)
                ->where('product_variants.sku', $sku);

            if ($this->isEditing()) {
                $query->where('products.id', '!=', $this->product->id);
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    "variants.{$index}.sku" => ["The SKU '{$sku}' is already used by another variant in this store."],
                ]);
            }
        }
    }

    /**
     * Store pending uploads on the public disk and queue processing
     * (spec 03 §4 media section).
     */
    private function storeUploadedMedia(Product $product): void
    {
        $position = (int) $product->media()->max('position');

        foreach ($this->newMedia as $file) {
            $path = $file->store("media/{$product->id}/originals", 'public');

            $media = $product->media()->create([
                'type' => MediaType::Image,
                'storage_key' => $path,
                'alt_text' => '',
                'position' => ++$position,
                'status' => MediaStatus::Processing,
            ]);

            ProcessMediaUpload::dispatch($media);
        }
    }

    /**
     * Persist edited alt texts for existing media items.
     */
    private function persistMediaAltTexts(Product $product): void
    {
        foreach ($this->media as $media) {
            $product->media()->whereKey($media['id'])->update(['alt_text' => $media['alt_text'] ?? '']);
        }
    }

    /**
     * Persist current grid order to the database.
     */
    private function persistMediaOrder(): void
    {
        if ($this->product === null) {
            return;
        }

        foreach ($this->media as $position => $media) {
            $this->product->media()->whereKey($media['id'])->update(['position' => $position]);
        }
    }

    /**
     * Load the product's data into the form properties (edit mode).
     */
    private function loadFromProduct(Product $product): void
    {
        $product->loadMissing(['options.values', 'variants.optionValues', 'variants.inventoryItem', 'media', 'collections']);

        $this->title = $product->title;
        $this->handle = $product->handle;
        $this->descriptionHtml = (string) ($product->description_html ?? '');
        $this->status = $product->status->value;
        $this->vendor = (string) ($product->vendor ?? '');
        $this->productType = (string) ($product->product_type ?? '');
        $this->tags = implode(', ', $product->tags ?? []);
        $this->publishedAt = $product->published_at?->format('Y-m-d\TH:i');
        $this->collectionIds = $product->collections->pluck('id')->all();

        $this->options = $product->options->map(fn ($option): array => [
            'name' => $option->name,
            'values' => $option->values->pluck('value')->implode(', '),
        ])->values()->all();

        $this->variants = $product->variants->map(function ($variant): array {
            $optionValues = $variant->optionValues->pluck('value')->values()->all();

            return [
                'key' => $optionValues === [] ? 'default' : $this->variantKey($optionValues),
                'label' => $optionValues === [] ? 'Default' : implode(' / ', $optionValues),
                'id' => $variant->id,
                'optionValues' => $optionValues,
                'sku' => (string) ($variant->sku ?? ''),
                'price' => $variant->price_amount,
                'compareAtPrice' => $variant->compare_at_amount,
                'barcode' => (string) ($variant->barcode ?? ''),
                'weight_g' => $variant->weight_g,
                'quantity' => $variant->inventoryItem?->quantity_on_hand ?? 0,
                'policy' => $variant->inventoryItem?->policy->value ?? InventoryPolicy::Deny->value,
                'requiresShipping' => $variant->requires_shipping,
            ];
        })->values()->all();

        if ($this->variants === []) {
            $this->variants = [$this->blankVariant()];
        }

        $this->media = $this->mediaFromProduct($product);
    }

    /**
     * Media list items for the grid.
     *
     * @return list<array{id: int, url: string, alt_text: string, position: int}>
     */
    private function mediaFromProduct(Product $product): array
    {
        return $product->media->map(fn (ProductMedia $media): array => [
            'id' => $media->id,
            'url' => $media->status === MediaStatus::Ready ? $media->urlFor('thumbnail') : $media->url(),
            'alt_text' => (string) ($media->alt_text ?? ''),
            'position' => $media->position,
        ])->values()->all();
    }

    /**
     * A blank variant row with sensible defaults.
     *
     * @return array<string, mixed>
     */
    private function blankVariant(): array
    {
        return [
            'key' => 'default',
            'label' => 'Default',
            'id' => null,
            'optionValues' => [],
            'sku' => '',
            'price' => 0,
            'compareAtPrice' => null,
            'barcode' => '',
            'weight_g' => null,
            'quantity' => 0,
            'policy' => InventoryPolicy::Deny->value,
            'requiresShipping' => true,
        ];
    }

    /**
     * Stable key for a combination of option values.
     *
     * @param  list<string>  $values
     */
    private function variantKey(array $values): string
    {
        return mb_strtolower(implode('/', $values));
    }

    /**
     * Cartesian product of option value sets.
     *
     * @param  list<list<string>>  $sets
     * @return list<list<string>>
     */
    private function cartesian(array $sets): array
    {
        $result = [[]];

        foreach ($sets as $set) {
            $next = [];

            foreach ($result as $combination) {
                foreach ($set as $value) {
                    $next[] = array_merge($combination, [$value]);
                }
            }

            $result = $next;
        }

        return $result;
    }
}
