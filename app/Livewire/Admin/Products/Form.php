<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\MediaService;
use App\Services\ProductService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use ResolvesAdminStore, WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public string $handle = '';

    public ?string $publishedAt = null;

    /** @var array<int> */
    public array $collectionIds = [];

    /** @var array<int, array{name: string, values: string}> */
    public array $options = [];

    /** @var array<int, array<string, mixed>> */
    public array $variants = [];

    /** @var array<int, array<string, mixed>> */
    public array $media = [];

    /** @var array<int, mixed> */
    public array $newMedia = [];

    public bool $showDeleteModal = false;

    public bool $seoOpen = false;

    public function mount(?Product $product = null): void
    {
        if ($product !== null && $product->exists) {
            abort_if($product->store_id !== $this->store()->getKey(), 404);

            Gate::authorize('view', $product);

            $this->product = $product->load([
                'productOptions.productOptionValues',
                'productVariants.inventoryItem',
                'productVariants.optionValues.productOption',
                'productMedia',
                'collections',
            ]);

            $this->title = $product->title;
            $this->descriptionHtml = (string) $product->description_html;
            $this->status = $product->status->value;
            $this->vendor = (string) $product->vendor;
            $this->productType = (string) $product->product_type;
            $this->tags = implode(', ', $product->tags ?? []);
            $this->handle = $product->handle;
            $this->publishedAt = $product->published_at?->format('Y-m-d\TH:i');
            $this->collectionIds = $product->collections->pluck('id')->map(fn ($id): int => (int) $id)->all();

            $this->options = $product->productOptions
                ->sortBy('position')
                ->map(fn ($option): array => [
                    'name' => $option->name,
                    'values' => $option->productOptionValues->sortBy('position')->pluck('value')->implode(', '),
                ])
                ->values()
                ->all();

            $this->variants = $product->productVariants
                ->sortBy('position')
                ->map(fn ($variant): array => [
                    'id' => $variant->getKey(),
                    'label' => $variant->optionValues->sortBy('product_option_id')->pluck('value')->implode(' / ') ?: 'Default',
                    'sku' => (string) $variant->sku,
                    'price' => number_format($variant->price_amount / 100, 2, '.', ''),
                    'compare_at' => $variant->compare_at_amount !== null ? number_format($variant->compare_at_amount / 100, 2, '.', '') : '',
                    'quantity' => $variant->inventoryItem?->quantity_on_hand ?? 0,
                    'requires_shipping' => (bool) $variant->requires_shipping,
                    'is_default' => (bool) $variant->is_default,
                    'option_values' => $variant->optionValues->map(fn ($value): array => [
                        'option_name' => $value->productOption?->name ?? '',
                        'value' => $value->value,
                    ])->all(),
                ])
                ->values()
                ->all();

            $this->media = $product->productMedia
                ->sortBy('position')
                ->map(fn ($item): array => [
                    'id' => $item->getKey(),
                    'url' => Storage::disk('public')->url($item->storage_key),
                    'alt_text' => (string) $item->alt_text,
                    'position' => $item->position,
                ])
                ->values()
                ->all();
        } else {
            Gate::authorize('create', Product::class);

            $this->product = null;
            $this->variants = [
                [
                    'id' => null,
                    'label' => 'Default',
                    'sku' => '',
                    'price' => '',
                    'compare_at' => '',
                    'quantity' => 0,
                    'requires_shipping' => true,
                    'is_default' => true,
                    'option_values' => [],
                ],
            ];
        }
    }

    public function updatedTitle(): void
    {
        if ($this->product === null && $this->handle === '') {
            $this->handle = Str::slug($this->title);
        }
    }

    public function updatedOptions(): void
    {
        $this->generateVariants();
    }

    public function updatedNewMedia(): void
    {
        $this->validate(['newMedia.*' => 'image|max:10240']);

        if ($this->product !== null) {
            $this->uploadMedia();
        }
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

    public function addOptionValue(int $optionIndex, string $value = ''): void
    {
        $value = trim($value);

        if ($value === '' || ! isset($this->options[$optionIndex])) {
            return;
        }

        $current = $this->options[$optionIndex]['values'];
        $this->options[$optionIndex]['values'] = $current === '' ? $value : $current.', '.$value;
        $this->generateVariants();
    }

    public function removeOptionValue(int $optionIndex, string $value): void
    {
        if (! isset($this->options[$optionIndex])) {
            return;
        }

        $values = array_values(array_filter(
            array_map(trim(...), explode(',', $this->options[$optionIndex]['values'])),
            fn (string $existing): bool => $existing !== '' && $existing !== $value,
        ));

        $this->options[$optionIndex]['values'] = implode(', ', $values);
        $this->generateVariants();
    }

    public function generateVariants(): void
    {
        $parsed = [];

        foreach ($this->options as $option) {
            $name = trim($option['name']);
            $values = array_values(array_filter(array_map(trim(...), explode(',', $option['values']))));

            if ($name !== '' && $values !== []) {
                $parsed[] = ['name' => $name, 'values' => $values];
            }
        }

        if ($parsed === []) {
            if ($this->variants === []) {
                $this->variants = [[
                    'id' => null, 'label' => 'Default', 'sku' => '', 'price' => '',
                    'compare_at' => '', 'quantity' => 0, 'requires_shipping' => true,
                    'is_default' => true, 'option_values' => [],
                ]];
            }

            return;
        }

        $combos = [[]];

        foreach ($parsed as $option) {
            $next = [];

            foreach ($combos as $combo) {
                foreach ($option['values'] as $value) {
                    $next[] = [...$combo, ['option_name' => $option['name'], 'value' => $value]];
                }
            }

            $combos = $next;
        }

        $existing = [];

        foreach ($this->variants as $variant) {
            $existing[$variant['label']] = $variant;
        }

        $this->variants = array_map(function (array $combo, int $index) use ($existing): array {
            $label = implode(' / ', array_column($combo, 'value'));
            $prior = $existing[$label] ?? [];

            return [
                'id' => $prior['id'] ?? null,
                'label' => $label,
                'sku' => $prior['sku'] ?? '',
                'price' => $prior['price'] ?? '',
                'compare_at' => $prior['compare_at'] ?? '',
                'quantity' => $prior['quantity'] ?? 0,
                'requires_shipping' => $prior['requires_shipping'] ?? true,
                'is_default' => $index === 0,
                'option_values' => $combo,
            ];
        }, $combos, array_keys($combos));
    }

    public function uploadMedia(): void
    {
        if ($this->product === null || $this->newMedia === []) {
            return;
        }

        Gate::authorize('update', $this->product);

        $media = app(MediaService::class);

        foreach ($this->newMedia as $file) {
            $record = $media->upload($this->product->refresh(), $file);

            $this->media[] = [
                'id' => $record->getKey(),
                'url' => Storage::disk('public')->url($record->storage_key),
                'alt_text' => '',
                'position' => $record->position,
            ];
        }

        $this->newMedia = [];
        $this->toast('success', 'Media uploaded.');
    }

    public function removeMedia(int $mediaId): void
    {
        if ($this->product === null) {
            return;
        }

        Gate::authorize('update', $this->product);

        $record = ProductMedia::query()
            ->where('product_id', $this->product->getKey())
            ->whereKey($mediaId)
            ->firstOrFail();

        app(MediaService::class)->deleteMedia($record);

        $this->media = array_values(array_filter($this->media, fn (array $item): bool => $item['id'] !== $mediaId));
        $this->toast('success', 'Media removed.');
    }

    /**
     * @param  array<int>  $order  Media IDs in the new order.
     */
    public function reorderMedia(array $order): void
    {
        if ($this->product === null) {
            return;
        }

        Gate::authorize('update', $this->product);

        $media = app(MediaService::class);

        foreach (array_values($order) as $position => $mediaId) {
            $record = ProductMedia::query()
                ->where('product_id', $this->product->getKey())
                ->whereKey((int) $mediaId)
                ->first();

            if ($record !== null) {
                $media->updateMedia($record, ['position' => $position]);
            }
        }

        $ordered = [];

        foreach (array_values($order) as $mediaId) {
            foreach ($this->media as $item) {
                if ($item['id'] === (int) $mediaId) {
                    $ordered[] = $item;
                }
            }
        }

        $this->media = $ordered === [] ? $this->media : $ordered;
    }

    public function updateMediaAlt(int $mediaId, string $alt): void
    {
        if ($this->product === null) {
            return;
        }

        Gate::authorize('update', $this->product);

        $record = ProductMedia::query()
            ->where('product_id', $this->product->getKey())
            ->whereKey($mediaId)
            ->firstOrFail();

        app(MediaService::class)->updateMedia($record, ['alt_text' => $alt]);

        foreach ($this->media as $index => $item) {
            if ($item['id'] === $mediaId) {
                $this->media[$index]['alt_text'] = $alt;
            }
        }
    }

    public function save(): mixed
    {
        if ($this->product !== null) {
            Gate::authorize('update', $this->product);
        } else {
            Gate::authorize('create', Product::class);
        }

        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'descriptionHtml' => 'nullable|string|max:65535',
            'status' => 'required|in:draft,active,archived',
            'vendor' => 'nullable|string|max:255',
            'productType' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'handle' => 'nullable|string|max:255',
            'publishedAt' => 'nullable|date',
            'collectionIds' => 'nullable|array',
            'collectionIds.*' => 'integer|exists:collections,id',
            'variants' => 'required|array|min:1',
            'variants.*.sku' => 'nullable|string|max:255',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.compare_at' => 'nullable|numeric|min:0',
            'variants.*.quantity' => 'required|integer|min:0',
        ]);

        $services = app(ProductService::class);
        $payload = $this->servicePayload($validated);

        if ($this->product !== null) {
            $product = $services->update($this->product, $payload);
            $this->applyPublishedAt($product);
            $this->product = $product->refresh();
            $this->toast('success', 'Product saved.');

            return null;
        }

        $product = $services->create($this->store(), $payload);
        $this->applyPublishedAt($product);

        foreach ($this->newMedia as $file) {
            app(MediaService::class)->upload($product->refresh(), $file);
        }

        $this->flashToast('success', 'Product saved.');

        return redirect()->route('admin.products.index');
    }

    public function deleteProduct(): mixed
    {
        abort_if($this->product === null, 404);

        Gate::authorize('delete', $this->product);

        $services = app(ProductService::class);

        if ($this->product->status === ProductStatus::Draft) {
            $services->delete($this->product);
        } else {
            $services->archive($this->product);
        }

        $this->flashToast('success', 'Product archived.');

        return redirect()->route('admin.products.index');
    }

    /**
     * @return array<string, mixed>
     */
    public function getIsEditingProperty(): bool
    {
        return $this->product !== null;
    }

    public function render()
    {
        return view('livewire.admin.products.form', [
            'availableCollections' => Collection::query()
                ->where('store_id', $this->store()->getKey())
                ->orderBy('title')
                ->get(),
            'isEditing' => $this->product !== null,
        ])->layout('livewire.admin.layout.app', ['title' => $this->product !== null ? $this->product->title : 'Add product']);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function servicePayload(array $validated): array
    {
        $options = [];
        $position = 1;

        foreach ($this->options as $option) {
            $name = trim($option['name']);
            $values = array_values(array_filter(array_map(trim(...), explode(',', $option['values']))));

            if ($name === '' || $values === []) {
                continue;
            }

            $options[] = ['name' => $name, 'position' => $position++, 'values' => $values];
        }

        $variants = [];

        foreach ($validated['variants'] as $index => $variant) {
            $entry = [
                'sku' => $variant['sku'] !== '' ? $variant['sku'] : null,
                'price_amount' => (int) round(((float) $variant['price']) * 100),
                'compare_at_amount' => $variant['compare_at'] !== '' && $variant['compare_at'] !== null
                    ? (int) round(((float) $variant['compare_at']) * 100)
                    : null,
                'requires_shipping' => (bool) ($this->variants[$index]['requires_shipping'] ?? true),
                'is_default' => $index === 0,
                'position' => $index + 1,
                'status' => 'active',
                'option_values' => $this->variants[$index]['option_values'] ?? [],
                'inventory' => [
                    'quantity_on_hand' => (int) $variant['quantity'],
                    'policy' => 'deny',
                ],
            ];

            if (! empty($this->variants[$index]['id'])) {
                $entry['id'] = (int) $this->variants[$index]['id'];
            }

            $variants[] = $entry;
        }

        return [
            'title' => $validated['title'],
            'description_html' => $validated['descriptionHtml'] !== '' ? $validated['descriptionHtml'] : null,
            'status' => $validated['status'],
            'vendor' => $validated['vendor'] !== '' ? $validated['vendor'] : null,
            'product_type' => $validated['productType'] !== '' ? $validated['productType'] : null,
            'tags' => array_values(array_filter(array_map(trim(...), explode(',', (string) ($validated['tags'] ?? ''))))),
            'handle' => $validated['handle'] !== '' && $validated['handle'] !== null ? $validated['handle'] : Str::slug($validated['title']),
            'collections' => array_map('intval', $validated['collectionIds'] ?? []),
            'options' => $options,
            'variants' => $variants,
        ];
    }

    protected function applyPublishedAt(Product $product): void
    {
        if ($this->publishedAt !== null && $this->publishedAt !== '') {
            $product->published_at = $this->publishedAt;
            $product->save();
        }
    }
}
