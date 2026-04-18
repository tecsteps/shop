<?php

namespace App\Livewire\Admin\Products;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $title = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $product_type = '';

    public string $description_html = '';

    public string $tags_input = '';

    public int $price_amount = 0;

    public string $sku = '';

    public $image = null;

    /** @var array<int, array<string, mixed>> */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $product->loadMissing('variants', 'media');
            $this->product = $product;
            $this->title = (string) $product->title;
            $this->status = $product->status?->value ?? 'draft';
            $this->vendor = (string) $product->vendor;
            $this->product_type = (string) $product->product_type;
            $this->description_html = (string) $product->description_html;
            $this->tags_input = implode(', ', (array) $product->tags);
            $this->variants = $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'sku' => $v->sku,
                'price_amount' => $v->price_amount,
            ])->toArray();
        }
    }

    public function save(ProductService $service): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,active,archived',
            'vendor' => 'nullable|string|max:255',
            'product_type' => 'nullable|string|max:255',
            'description_html' => 'nullable|string',
            'tags_input' => 'nullable|string',
        ]);

        $tags = array_values(array_filter(array_map('trim', explode(',', (string) $this->tags_input))));

        $payload = [
            'title' => $data['title'],
            'status' => ProductStatus::from($data['status']),
            'vendor' => $data['vendor'] ?? null,
            'product_type' => $data['product_type'] ?? null,
            'description_html' => $data['description_html'] ?? null,
            'tags' => $tags,
            'price_amount' => $this->price_amount,
        ];

        if ($this->product && $this->product->exists) {
            $current = $this->product->status;
            $newStatus = $payload['status'];
            unset($payload['status'], $payload['price_amount']);
            $service->update($this->product, $payload);
            if ($current !== $newStatus) {
                $service->transitionStatus($this->product->fresh(), $newStatus);
            }
            $product = $this->product->fresh();
        } else {
            $store = app('current_store');
            $product = $service->create($store, $payload);
            $this->product = $product;
        }

        foreach ($this->variants as $entry) {
            if (! empty($entry['id'])) {
                ProductVariant::query()->whereKey($entry['id'])->update([
                    'sku' => $entry['sku'] ?? null,
                    'price_amount' => (int) ($entry['price_amount'] ?? 0),
                ]);
            }
        }

        if ($this->image) {
            $path = $this->image->store('product-media', 'public');
            ProductMedia::create([
                'product_id' => $product->id,
                'type' => MediaType::Image,
                'storage_key' => $path,
                'alt_text' => $product->title,
                'status' => MediaStatus::Ready,
                'mime_type' => $this->image->getMimeType(),
                'byte_size' => $this->image->getSize(),
                'position' => $product->media()->count(),
                'created_at' => now(),
            ]);
            $this->image = null;
        }

        session()->flash('success', 'Product saved.');

        $this->redirect(route('admin.products.edit', $product), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.products.form', [
            'statuses' => ProductStatus::cases(),
        ]);
    }
}
