<?php

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Support\HandleGenerator;
use Livewire\Component;

class Form extends Component
{
    public ?int $productId = null;

    public string $title = '';

    public string $description = '';

    public string $status = 'draft';

    public string $vendor = '';

    public string $product_type = '';

    public string $tags = '';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,archived'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'product_type' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string'],
        ];
    }

    public function mount(?int $productId = null): void
    {
        if ($productId) {
            $product = Product::findOrFail($productId);
            $this->productId = $product->id;
            $this->title = $product->title;
            $this->description = $product->description ?? '';
            $this->status = $product->status->value;
            $this->vendor = $product->vendor ?? '';
            $this->product_type = $product->product_type ?? '';
            $this->tags = $product->tags ? implode(', ', $product->tags) : '';
        }
    }

    public function save(): mixed
    {
        $this->validate();

        $store = app('current_store');
        $tags = $this->tags ? array_map('trim', explode(',', $this->tags)) : [];

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update([
                'title' => $this->title,
                'description' => $this->description ?: null,
                'status' => $this->status,
                'vendor' => $this->vendor ?: null,
                'product_type' => $this->product_type ?: null,
                'tags' => $tags,
            ]);
            $this->dispatch('toast', type: 'success', message: 'Product updated.');
        } else {
            $handle = app(HandleGenerator::class)->generate($this->title, 'products', $store->id);
            $product = Product::create([
                'store_id' => $store->id,
                'title' => $this->title,
                'handle' => $handle,
                'description' => $this->description ?: null,
                'status' => $this->status,
                'vendor' => $this->vendor ?: null,
                'product_type' => $this->product_type ?: null,
                'tags' => $tags,
            ]);
            session()->flash('toast', ['type' => 'success', 'message' => 'Product created.']);

            return redirect()->route('admin.products.edit', $product);
        }

        return null;
    }

    public function render(): mixed
    {
        $isEdit = (bool) $this->productId;

        return view('livewire.admin.products.form', [
            'isEdit' => $isEdit,
            'product' => $isEdit ? Product::with(['variants', 'media'])->find($this->productId) : null,
        ])->layout('layouts.admin.app', [
            'title' => $isEdit ? "Edit {$this->title}" : 'New Product',
        ]);
    }
}
