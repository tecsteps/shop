<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Collection'])]
class Form extends Component
{
    public ?Collection $collection = null;

    public string $title = '';

    public string $description_html = '';

    public string $status = 'draft';

    public string $productSearch = '';

    /** @var array<int> */
    public array $selectedProducts = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection && $collection->exists) {
            $this->authorize('update', $collection);
            $this->collection = $collection;
            $this->title = $collection->title;
            $this->description_html = $collection->description_html ?? '';
            $this->status = $collection->status->value;
            $this->selectedProducts = $collection->products()->pluck('products.id')->all();
        }
    }

    public function addProduct(int $productId): void
    {
        if (! in_array($productId, $this->selectedProducts)) {
            $this->selectedProducts[] = $productId;
        }
        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->selectedProducts = array_values(array_diff($this->selectedProducts, [$productId]));
    }

    public function save(): void
    {
        if ($this->collection) {
            $this->authorize('update', $this->collection);
        } else {
            $this->authorize('create', Collection::class);
        }

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,active,archived'],
        ]);

        $store = app('current_store');

        $data = [
            'store_id' => $store->id,
            'title' => $this->title,
            'handle' => Str::slug($this->title),
            'description_html' => $this->description_html
                ? strip_tags($this->description_html, '<p><br><strong><em><ul><ol><li><h2><h3><h4><a><img>')
                : null,
            'status' => $this->status,
            'type' => 'manual',
        ];

        if ($this->collection) {
            $this->collection->update($data);
            $collection = $this->collection;
        } else {
            $collection = Collection::query()->create($data);
        }

        $syncData = [];
        foreach ($this->selectedProducts as $pos => $productId) {
            $syncData[$productId] = ['position' => $pos];
        }
        $collection->products()->sync($syncData);

        session()->flash('success', $this->collection ? 'Collection updated.' : 'Collection created.');
        $this->redirect(route('admin.collections.index'));
    }

    public function render(): mixed
    {
        $store = app('current_store');

        $searchResults = [];
        if ($this->productSearch) {
            $searchResults = Product::query()
                ->where('store_id', $store->id)
                ->where('title', 'like', "%{$this->productSearch}%")
                ->whereNotIn('id', $this->selectedProducts)
                ->limit(10)
                ->get();
        }

        $selectedProductModels = Product::query()->whereIn('id', $this->selectedProducts)->get();

        return view('livewire.admin.collections.form', [
            'searchResults' => $searchResults,
            'selectedProductModels' => $selectedProductModels,
        ]);
    }
}
