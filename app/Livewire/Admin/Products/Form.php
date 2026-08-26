<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Collection;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use DispatchesToasts, WithFileUploads;

    #[Layout('layouts.admin.app')]
    public ?Product $product = null;

    public string $title = '';

    public string $descriptionHtml = '';

    public string $status = 'draft';

    public ?string $vendor = null;

    public ?string $productType = null;

    public string $tags = '';

    public string $handle = '';

    public ?string $publishedAt = null;

    /** @var list<int> */
    public array $collectionIds = [];

    /**
     * @var list<array{id?: int, sku: ?string, price: float, compareAtPrice: ?float, quantity: int, requiresShipping: bool}>
     */
    public array $variants = [];

    /**
     * @var list<array{id: int, url: string, alt_text: ?string, position: int}>
     */
    public array $existingMedia = [];

    /** @var array<int, mixed> */
    public array $newMedia = [];

    public bool $confirmingDelete = false;

    public bool $showSeo = false;

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->authorize('update', $product);

            $this->product = $product->load(['variants.inventoryItem', 'media', 'collections']);

            $this->title = $product->title;
            $this->descriptionHtml = (string) $product->description_html;
            $this->status = $product->status;
            $this->vendor = $product->vendor;
            $this->productType = $product->product_type;
            $this->tags = implode(', ', $product->tags ?? []);
            $this->handle = $product->handle;
            $this->publishedAt = $product->published_at?->format('Y-m-d\TH:i');
            $this->collectionIds = $product->collections->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->variants = $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price_amount / 100,
                'compareAtPrice' => $variant->compare_at_amount !== null ? $variant->compare_at_amount / 100 : null,
                'quantity' => $variant->inventoryItem?->quantity_on_hand ?? 0,
                'requiresShipping' => (bool) $variant->requires_shipping,
            ])->all();
            $this->existingMedia = $product->media->sortBy('position')->values()->map(fn ($media) => [
                'id' => $media->id,
                'url' => Storage::disk('public')->url($media->storage_key),
                'alt_text' => $media->alt_text,
                'position' => $media->position,
            ])->all();
        } else {
            $this->authorize('create', Product::class);
            $this->variants = [$this->newVariantRow()];
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->product !== null;
    }

    #[Computed]
    public function availableCollections()
    {
        return Collection::query()
            ->where('store_id', app('current_store')->id)
            ->orderBy('title')
            ->get();
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,archived'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
            'handle' => ['nullable', 'string', 'max:255', Rule::unique('products', 'handle')
                ->where('store_id', app('current_store')->id)
                ->ignore($this->product?->id)],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compareAtPrice' => ['nullable', 'numeric', 'min:0'],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
            'newMedia.*' => ['image', 'max:5120'],
        ]);

        $store = app('current_store');
        $service = app(ProductService::class);

        $wasCreating = $this->product === null;

        $data = [
            'title' => $this->title,
            'status' => $this->status,
            'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
            'vendor' => $this->vendor ?: null,
            'product_type' => $this->productType ?: null,
            'tags' => $this->parseTags(),
        ];

        if ($this->handle !== '') {
            $data['handle'] = $this->handle;
        }

        $product = $this->product === null
            ? $service->create($store, $data)
            : $service->update($this->product, $data);

        if ($this->status === 'active' && $product->published_at === null) {
            $product->update(['published_at' => $this->publishedAt ? Carbon::parse($this->publishedAt) : now()]);
        } elseif ($this->publishedAt !== null) {
            $product->update(['published_at' => Carbon::parse($this->publishedAt)]);
        }

        $this->syncVariants($product, $store->default_currency);
        $this->syncMedia($product);

        $product->collections()->sync($this->collectionIds);

        $this->product = $product->fresh(['variants.inventoryItem', 'media', 'collections']);
        $this->existingMedia = collect($this->product->media->sortBy('position'))
            ->map(fn ($media) => [
                'id' => $media->id,
                'url' => Storage::disk('public')->url($media->storage_key),
                'alt_text' => $media->alt_text,
                'position' => $media->position,
            ])
            ->all();

        $this->toast('Product saved');

        if ($wasCreating) {
            $this->redirect(route('admin.products.edit', $product), navigate: true);
        }
    }

    public function addVariant(): void
    {
        $this->variants[] = $this->newVariantRow();
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function uploadMedia(): void
    {
        $this->validate(['newMedia.*' => ['image', 'max:5120']]);
        $this->toast('Media uploaded', 'info');
    }

    public function removeMedia(int $mediaId): void
    {
        $media = $this->product?->media()->find($mediaId);

        if ($media) {
            Storage::disk('public')->delete($media->storage_key);
            $media->delete();
        }

        $this->existingMedia = collect($this->existingMedia)
            ->reject(fn ($item) => (int) $item['id'] === $mediaId)
            ->values()
            ->all();
    }

    public function updateMediaAlt(int $mediaId, string $alt): void
    {
        $media = $this->product?->media()->find($mediaId);

        if ($media) {
            $media->update(['alt_text' => $alt]);
        }

        $this->existingMedia = collect($this->existingMedia)->map(function ($item) use ($mediaId, $alt) {
            if ((int) $item['id'] === $mediaId) {
                $item['alt_text'] = $alt;
            }

            return $item;
        })->all();
    }

    public function deleteProduct(): void
    {
        if (! $this->product) {
            return;
        }

        $this->authorize('delete', $this->product);
        $this->confirmingDelete = false;

        try {
            app(ProductService::class)->transitionStatus($this->product, ProductStatus::Archived);
            $this->toast('Product archived');
            $this->redirect(route('admin.products.index'), navigate: true);
        } catch (\Throwable $e) {
            $this->toast($e->getMessage(), 'error');
        }
    }

    /**
     * @return array{sku: null, price: float, compareAtPrice: null, quantity: int, requiresShipping: bool}
     */
    private function newVariantRow(): array
    {
        return [
            'sku' => null,
            'price' => 0.0,
            'compareAtPrice' => null,
            'quantity' => 0,
            'requiresShipping' => true,
        ];
    }

    /**
     * @return list<string>
     */
    private function parseTags(): array
    {
        return collect(explode(',', $this->tags))
            ->map(fn ($tag) => trim($tag))
            ->filter(fn ($tag) => $tag !== '')
            ->values()
            ->all();
    }

    private function syncVariants(Product $product, string $currency): void
    {
        // ProductService::create() auto-creates a default variant; reuse it for
        // the first form row so we don't end up with a redundant empty variant.
        $unassignedDefault = $product->variants()->where('is_default', true)->first();

        foreach ($this->variants as $row) {
            $price = (int) round(((float) ($row['price'] ?? 0)) * 100);
            $compareAt = ($row['compareAtPrice'] ?? null) !== null && (float) $row['compareAtPrice'] > 0
                ? (int) round(((float) $row['compareAtPrice']) * 100)
                : null;
            $quantity = max(0, (int) ($row['quantity'] ?? 0));

            $variant = isset($row['id']) && $row['id'] !== null
                ? $product->variants()->find($row['id'])
                : $unassignedDefault;

            if ($variant) {
                if ($unassignedDefault !== null && (! isset($row['id']) || $row['id'] === null)) {
                    $unassignedDefault = null;
                }

                $variant->update([
                    'sku' => ($row['sku'] ?? null) !== '' ? ($row['sku'] ?? null) : null,
                    'price_amount' => $price,
                    'compare_at_amount' => $compareAt,
                    'requires_shipping' => (bool) ($row['requiresShipping'] ?? true),
                ]);

                $item = $variant->inventoryItem;

                if ($item) {
                    $item->update(['quantity_on_hand' => $quantity]);
                }
            } else {
                $variant = $product->variants()->create([
                    'sku' => ($row['sku'] ?? null) !== '' ? ($row['sku'] ?? null) : null,
                    'price_amount' => $price,
                    'compare_at_amount' => $compareAt,
                    'currency' => $currency,
                    'requires_shipping' => (bool) ($row['requiresShipping'] ?? true),
                    'is_default' => $product->variants()->count() === 0,
                    'position' => $product->variants()->count(),
                    'status' => 'active',
                ]);

                $variant->inventoryItem()->create([
                    'store_id' => $product->store_id,
                    'quantity_on_hand' => $quantity,
                    'quantity_reserved' => 0,
                    'policy' => 'deny',
                ]);
            }
        }
    }

    private function syncMedia(Product $product): void
    {
        if ($this->newMedia === []) {
            return;
        }

        $position = $product->media()->count();

        foreach ($this->newMedia as $file) {
            $path = $file->store('products/'.$product->id, 'public');

            $product->media()->create([
                'type' => 'image',
                'storage_key' => $path,
                'mime_type' => method_exists($file, 'getMimeType') ? $file->getMimeType() : null,
                'byte_size' => $file->getSize(),
                'position' => $position++,
                'status' => 'processing',
            ]);
        }

        $this->newMedia = [];
    }

    public function render()
    {
        return view('livewire.admin.products.form');
    }
}
