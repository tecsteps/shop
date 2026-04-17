<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Product $product = null;

    public string $mode = 'create';

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:255')]
    public string $handle = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('nullable|string|max:255')]
    public string $vendor = '';

    #[Validate('nullable|string|max:255')]
    public string $productType = '';

    #[Validate('required|string|in:draft,active,archived')]
    public string $status = 'draft';

    #[Validate('nullable|string|max:500')]
    public string $tagsInput = '';

    #[Validate('nullable|integer|min:0')]
    public int $priceAmount = 0;

    #[Validate('nullable|string|max:100')]
    public string $sku = '';

    #[Validate('nullable|integer|min:0')]
    public int $inventoryQuantity = 0;

    public function mount(?Product $product = null): void
    {
        if ($product !== null && $product->exists) {
            $this->product = $product;
            $this->mode = 'edit';
            $this->title = (string) $product->title;
            $this->handle = (string) $product->handle;
            $this->description = (string) ($product->description_html ?? '');
            $this->vendor = (string) ($product->vendor ?? '');
            $this->productType = (string) ($product->product_type ?? '');
            $this->status = $product->status->value;
            $this->tagsInput = implode(', ', $product->tags ?? []);

            $defaultVariant = $product->variants()->where('is_default', true)->first()
                ?? $product->variants()->first();

            if ($defaultVariant !== null) {
                $this->priceAmount = (int) $defaultVariant->price_amount;
                $this->sku = (string) ($defaultVariant->sku ?? '');
            }
        }
    }

    public function save(ProductService $service): mixed
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        $tags = array_values(array_filter(array_map('trim', explode(',', $this->tagsInput))));

        $data = [
            'title' => $this->title,
            'handle' => $this->handle !== '' ? $this->handle : null,
            'description_html' => $this->description !== '' ? $this->description : null,
            'vendor' => $this->vendor !== '' ? $this->vendor : null,
            'product_type' => $this->productType !== '' ? $this->productType : null,
            'status' => $this->status,
            'tags' => $tags,
        ];

        if ($this->mode === 'create') {
            $product = $service->create($store, $data);

            $variant = $product->variants()->where('is_default', true)->first();
            if ($variant !== null) {
                $variant->update([
                    'price_amount' => $this->priceAmount,
                    'sku' => $this->sku !== '' ? $this->sku : null,
                ]);
            }
        } else {
            $product = $service->update($this->product, $data);
            $product->update(['status' => $this->status]);

            $variant = $product->variants()->where('is_default', true)->first()
                ?? $product->variants()->first();

            if ($variant !== null) {
                $variant->update([
                    'price_amount' => $this->priceAmount,
                    'sku' => $this->sku !== '' ? $this->sku : null,
                ]);
            }
        }

        session()->flash('status', 'Product saved.');

        return redirect()->route('admin.products.index');
    }

    public function render(): View
    {
        return view('livewire.admin.products.form', [
            'statuses' => ProductStatus::cases(),
        ]);
    }
}
