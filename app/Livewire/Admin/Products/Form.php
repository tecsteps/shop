<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Exceptions\DomainException;
use App\Jobs\ProcessMediaUpload;
use App\Livewire\Admin\AdminComponent;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Services\ProductService;
use App\Services\SearchService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;

class Form extends AdminComponent
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $productType = '';

    public string $tags = '';

    public string $handle = '';

    public ?string $publishedAt = null;

    /** @var list<int|string> */
    public array $collectionIds = [];

    /** @var list<array{name: string, values: list<string>}> */
    public array $options = [];

    /** @var list<array<string, mixed>> */
    public array $variants = [];

    /** @var list<array<string, mixed>> */
    public array $media = [];

    /** @var array<int, mixed> */
    public array $newMedia = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            abort_unless((int) $product->store_id === (int) $this->currentStore()->id, 404);
            $this->authorizeAction('view', $product);
            $this->product = $product->load(['options.values', 'variants.inventoryItem', 'variants.optionValues.option', 'media', 'collections']);
            $this->title = $product->title;
            $this->descriptionHtml = (string) $product->description_html;
            $this->status = (string) $this->enumValue($product->status);
            $this->vendor = (string) $product->vendor;
            $this->productType = (string) $product->product_type;
            $this->tags = implode(', ', (array) $product->tags);
            $this->handle = $product->handle;
            $this->publishedAt = $product->published_at?->format('Y-m-d\TH:i');
            $this->collectionIds = $product->collections->modelKeys();
            $this->options = $product->options->map(fn ($option): array => ['name' => $option->name, 'values' => $option->values->pluck('value')->all()])->all();
            $this->variants = $product->variants->map(fn ($variant): array => [
                'title' => $variant->optionValues->sortBy(fn ($value) => $value->option?->position)->pluck('value')->implode(' / ') ?: 'Default',
                'sku' => (string) $variant->sku, 'price' => (int) $variant->price_amount,
                'compareAtPrice' => $variant->compare_at_amount, 'quantity' => (int) ($variant->inventoryItem?->quantity_on_hand ?? 0),
                'requiresShipping' => (bool) $variant->requires_shipping,
            ])->all();
            $this->refreshMedia();
        } else {
            $this->authorizeAction('create', Product::class);
            $this->variants = [['title' => 'Default', 'sku' => '', 'price' => 0, 'compareAtPrice' => null, 'quantity' => 0, 'requiresShipping' => true]];
        }
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->product && $this->handle === '') {
            $this->handle = Str::slug($value);
        }
    }

    public function addOption(): void
    {
        if (count($this->options) < 3) {
            $this->options[] = ['name' => '', 'values' => ['']];
            $this->generateVariants();
        }
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
        $this->generateVariants();
    }

    public function addOptionValue(int $optionIndex): void
    {
        $this->options[$optionIndex]['values'][] = '';
    }

    public function removeOptionValue(int $optionIndex, int $valueIndex): void
    {
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
        $old = collect($this->variants)->keyBy('title');
        $groups = collect($this->options)->map(fn (array $option): array => array_values(array_filter(array_map('trim', $option['values'] ?? []))))->filter()->values()->all();
        $combinationCount = array_reduce($groups, fn (int $count, array $group): int => $count * count(array_unique($group)), 1);
        if ($combinationCount > 100) {
            throw ValidationException::withMessages(['options' => 'Product options may generate at most 100 variants.']);
        }
        $combinations = [[]];
        foreach ($groups as $group) {
            $combinations = collect($combinations)->flatMap(fn (array $prefix) => collect($group)->map(fn (string $value) => [...$prefix, $value]))->all();
        }
        $this->variants = collect($combinations ?: [[]])->map(function (array $values) use ($old): array {
            $title = implode(' / ', $values) ?: 'Default';

            return $old->get($title, ['title' => $title, 'sku' => '', 'price' => 0, 'compareAtPrice' => null, 'quantity' => 0, 'requiresShipping' => true]);
        })->values()->all();
    }

    public function save(): void
    {
        $id = $this->product?->id;
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'handle' => ['required', 'alpha_dash', 'max:255', Rule::unique('products', 'handle')->where('store_id', $this->currentStore()->id)->ignore($id)],
            'options' => ['array', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:100'],
            'options.*.values' => ['required', 'array', 'min:1'],
            'options.*.values.*' => ['required', 'string', 'max:100'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.compareAtPrice' => ['nullable', 'integer', 'min:0'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
            'variants.*.requiresShipping' => ['boolean'],
            'collectionIds' => ['array'],
            'collectionIds.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $this->currentStore()->id)],
            'newMedia.*' => ['image', 'max:5120'],
        ]);

        if ($this->status === 'active' && collect($validated['variants'])->doesntContain(fn (array $variant): bool => (int) $variant['price'] > 0)) {
            throw ValidationException::withMessages(['variants' => 'An active product needs at least one variant with a price greater than zero.']);
        }

        $service = app(ProductService::class);
        $data = [
            'title' => $validated['title'], 'description_html' => $validated['descriptionHtml'] ?: null,
            'vendor' => $validated['vendor'] ?: null, 'product_type' => $validated['productType'] ?: null,
            'tags' => array_values(array_filter(array_map('trim', explode(',', $validated['tags'] ?? '')))),
            'handle' => Str::slug($validated['handle']),
            'options' => collect($validated['options'])->map(fn (array $option): array => ['name' => trim($option['name']), 'values' => array_values(array_unique(array_map('trim', $option['values'])))])->all(),
        ];

        if ($this->product) {
            $this->authorizeAction('update', $this->product);
            $this->product = $service->update($this->product, $data);
        } else {
            $this->authorizeAction('create', Product::class);
            $this->product = $service->create($this->currentStore(), [...$data, 'status' => 'draft', 'variant' => ['price_amount' => 0]]);
        }

        $local = collect($this->variants)->keyBy('title');
        $this->product->load(['variants.optionValues.option', 'variants.inventoryItem']);
        foreach ($this->product->variants as $position => $variant) {
            $title = $variant->optionValues->sortBy(fn ($value) => $value->option?->position)->pluck('value')->implode(' / ') ?: 'Default';
            $row = $local->get($title, $this->variants[$position] ?? null);
            if (! $row) {
                continue;
            }
            $variant->update([
                'sku' => $row['sku'] ?: null, 'price_amount' => (int) $row['price'],
                'compare_at_amount' => filled($row['compareAtPrice']) ? (int) $row['compareAtPrice'] : null,
                'currency' => $this->currentStore()->default_currency, 'requires_shipping' => (bool) $row['requiresShipping'], 'position' => $position,
            ]);
            $variant->inventoryItem()->updateOrCreate([], ['store_id' => $this->currentStore()->id, 'quantity_on_hand' => (int) $row['quantity'], 'quantity_reserved' => (int) ($variant->inventoryItem?->quantity_reserved ?? 0), 'policy' => $variant->inventoryItem?->policy ?? 'deny']);
        }
        $this->product->collections()->sync(collect($this->collectionIds)->mapWithKeys(fn ($value, $position) => [(int) $value => ['position' => $position]])->all());
        $this->product->forceFill(['published_at' => $this->publishedAt ?: null])->save();
        try {
            $service->transitionStatus($this->product->refresh(), ProductStatus::from($this->status));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }
        $this->uploadMedia();
        app(SearchService::class)->syncProduct($this->product->refresh());
        $this->toast('Product saved successfully.');
        $this->redirect('/admin/products/'.$this->product->id.'/edit', navigate: true);
    }

    public function uploadMedia(): void
    {
        if (! $this->product || $this->newMedia === []) {
            return;
        }

        $this->authorizeAction('update', $this->product);
        $this->validate([
            'newMedia' => ['required', 'array', 'max:10'],
            'newMedia.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp,avif', 'max:5120'],
        ]);

        $position = (int) $this->product->media()->max('position') + 1;
        foreach ($this->newMedia as $file) {
            $path = $file->store('media/'.$this->product->id.'/originals', 'public');
            $media = $this->product->media()->create([
                'type' => 'image',
                'storage_key' => $path,
                'alt_text' => $this->product->title,
                'mime_type' => $file->getMimeType(),
                'byte_size' => $file->getSize(),
                'position' => $position++,
                'status' => 'processing',
            ]);
            ProcessMediaUpload::dispatch($media)->afterCommit();
        }
        $this->newMedia = [];
        $this->refreshMedia();
    }

    public function removeMedia(int $mediaId): void
    {
        abort_unless($this->product, 404);
        $this->authorizeAction('update', $this->product);
        $media = $this->product->media()->findOrFail($mediaId);
        Storage::disk('public')->delete($media->storage_key);
        $media->delete();
        $this->refreshMedia();
        $this->toast('Media removed.');
    }

    /** @param list<int> $order */
    public function reorderMedia(array $order): void
    {
        abort_unless($this->product, 404);
        $this->authorizeAction('update', $this->product);
        foreach ($order as $position => $id) {
            $this->product->media()->whereKey($id)->update(['position' => $position]);
        }
        $this->refreshMedia();
    }

    public function moveMedia(int $mediaId, string $direction): void
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 400);
        $ids = collect($this->media)->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();
        $index = array_search($mediaId, $ids, true);
        abort_if($index === false, 404);
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if (! isset($ids[$target])) {
            return;
        }
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        $this->reorderMedia($ids);
    }

    public function updateMediaAlt(int $mediaId, string $alt): void
    {
        abort_unless($this->product, 404);
        $this->authorizeAction('update', $this->product);
        $this->product->media()->whereKey($mediaId)->update(['alt_text' => Str::limit(trim($alt), 255, '')]);
        $this->refreshMedia();
    }

    public function deleteProduct(): void
    {
        abort_unless($this->product, 404);
        $this->authorizeAction('delete', $this->product);
        app(ProductService::class)->transitionStatus($this->product, ProductStatus::Archived);
        $this->toast('Product archived.');
        $this->redirect('/admin/products', navigate: true);
    }

    private function refreshMedia(): void
    {
        $this->media = $this->product?->media()->orderBy('position')->get()->map(fn (ProductMedia $media): array => ['id' => $media->id, 'url' => Storage::disk('public')->url($media->storage_key), 'alt_text' => $media->alt_text, 'position' => $media->position])->all() ?? [];
    }

    #[Computed]
    public function availableCollections(): iterable
    {
        return Collection::query()->orderBy('title')->get();
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->product?->exists === true;
    }

    public function render(): View
    {
        $label = $this->product?->title ?: 'Add product';

        return $this->admin(view('admin.products.form'), $label, [['label' => 'Products', 'url' => url('/admin/products')], ['label' => $label]]);
    }
}
