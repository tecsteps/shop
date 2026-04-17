<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    public int $productId;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $vendor = '';

    public string $productType = '';

    public string $status = 'draft';

    public string $tags = '';

    /**
     * @var array<int, array{id:int, sku:?string, price_amount:int, compare_at_amount:?int, weight_g:?int, quantity_on_hand:int}>
     */
    public array $variants = [];

    public function mount(int $product): void
    {
        $model = Product::query()->with(['variants.inventoryItem'])->findOrFail($product);
        $this->authorize('view', $model);

        $this->productId = (int) $model->getKey();
        $this->title = (string) $model->title;
        $this->handle = (string) $model->handle;
        $this->descriptionHtml = (string) ($model->description_html ?? '');
        $this->vendor = (string) ($model->vendor ?? '');
        $this->productType = (string) ($model->product_type ?? '');
        $this->status = $model->status->value;
        $this->tags = implode(', ', $model->tags ?? []);

        $this->variants = $model->variants->map(fn ($v): array => [
            'id' => (int) $v->getKey(),
            'sku' => $v->sku,
            'price_amount' => (int) $v->price_amount,
            'compare_at_amount' => $v->compare_at_amount !== null ? (int) $v->compare_at_amount : null,
            'weight_g' => $v->weight_g !== null ? (int) $v->weight_g : null,
            'quantity_on_hand' => (int) (optional($v->inventoryItem)->quantity_on_hand ?? 0),
        ])->values()->all();
    }

    public function save(ProductService $service): mixed
    {
        $model = Product::query()->findOrFail($this->productId);
        $this->authorize('update', $model);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'productType' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:'.implode(',', ProductStatus::values())],
            'tags' => ['nullable', 'string'],
            'variants.*.price_amount' => ['required', 'integer', 'min:0'],
            'variants.*.quantity_on_hand' => ['required', 'integer', 'min:0'],
        ]);

        $tagsArray = array_values(array_filter(array_map('trim', explode(',', $this->tags))));

        $service->update($model, [
            'title' => $this->title,
            'handle' => $this->handle,
            'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
            'vendor' => $this->vendor !== '' ? $this->vendor : null,
            'product_type' => $this->productType !== '' ? $this->productType : null,
            'tags' => $tagsArray,
        ]);

        foreach ($this->variants as $row) {
            $variant = $model->variants()->find($row['id']);

            if ($variant === null) {
                continue;
            }

            $variant->sku = $row['sku'] ?: null;
            $variant->price_amount = (int) $row['price_amount'];
            $variant->compare_at_amount = $row['compare_at_amount'];
            $variant->weight_g = $row['weight_g'];
            $variant->save();

            if ($variant->inventoryItem) {
                $variant->inventoryItem->quantity_on_hand = (int) $row['quantity_on_hand'];
                $variant->inventoryItem->save();
            }
        }

        $current = ProductStatus::from($model->fresh()->status->value);
        $target = ProductStatus::from($this->status);

        if ($current !== $target) {
            try {
                $service->transitionStatus($model->fresh(), $target);
            } catch (\Throwable $e) {
                $this->addError('status', $e->getMessage());

                return null;
            }
        }

        session()->flash('status', 'Product updated.');

        return redirect('/admin/products/'.$this->productId.'/edit');
    }

    public function render(): View
    {
        $product = Product::query()->with(['variants.inventoryItem', 'media'])->findOrFail($this->productId);

        return view('livewire.admin.products.edit', [
            'product' => $product,
        ]);
    }
}
