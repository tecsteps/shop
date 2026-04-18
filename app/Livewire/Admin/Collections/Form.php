<?php

namespace App\Livewire\Admin\Collections;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\Product;
use App\Support\HandleGenerator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Collection $collection = null;

    public string $title = '';

    public string $handle = '';

    public string $description_html = '';

    public string $type = 'manual';

    public string $status = 'active';

    /** @var array<int, int> */
    public array $product_ids = [];

    public string $productSearch = '';

    public function mount(?Collection $collection = null): void
    {
        if ($collection && $collection->exists) {
            $collection->loadMissing('products');
            $this->collection = $collection;
            $this->title = (string) $collection->title;
            $this->handle = (string) $collection->handle;
            $this->description_html = (string) $collection->description_html;
            $this->type = $collection->type?->value ?? 'manual';
            $this->status = $collection->status?->value ?? 'active';
            $this->product_ids = $collection->products->pluck('id')->toArray();
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'description_html' => 'nullable|string',
            'type' => 'required|in:manual,automated',
            'status' => 'required|in:draft,active,archived',
        ]);

        $store = app('current_store');
        $handle = $this->handle !== ''
            ? $this->handle
            : HandleGenerator::generate($this->title, 'collections', $store->id, $this->collection?->id);

        if ($this->collection && $this->collection->exists) {
            $this->collection->update([
                ...$data,
                'handle' => $handle,
            ]);
            $collection = $this->collection;
        } else {
            $collection = Collection::create([
                'store_id' => $store->id,
                ...$data,
                'handle' => $handle,
            ]);
            $this->collection = $collection;
        }

        $sync = [];
        foreach ($this->product_ids as $idx => $pid) {
            $sync[$pid] = ['position' => $idx];
        }
        $collection->products()->sync($sync);

        session()->flash('success', 'Collection saved.');

        $this->redirect(route('admin.collections.edit', $collection), navigate: true);
    }

    public function addProduct(int $id): void
    {
        if (! in_array($id, $this->product_ids, true)) {
            $this->product_ids[] = $id;
        }
    }

    public function removeProduct(int $id): void
    {
        $this->product_ids = array_values(array_filter($this->product_ids, fn ($pid) => (int) $pid !== $id));
    }

    public function moveUp(int $id): void
    {
        $idx = array_search($id, $this->product_ids, true);
        if ($idx !== false && $idx > 0) {
            [$this->product_ids[$idx - 1], $this->product_ids[$idx]] = [$this->product_ids[$idx], $this->product_ids[$idx - 1]];
        }
    }

    public function moveDown(int $id): void
    {
        $idx = array_search($id, $this->product_ids, true);
        if ($idx !== false && $idx < count($this->product_ids) - 1) {
            [$this->product_ids[$idx], $this->product_ids[$idx + 1]] = [$this->product_ids[$idx + 1], $this->product_ids[$idx]];
        }
    }

    public function render()
    {
        $searchResults = $this->productSearch === ''
            ? collect()
            : Product::query()
                ->where('title', 'like', '%'.$this->productSearch.'%')
                ->whereNotIn('id', $this->product_ids)
                ->limit(10)
                ->get(['id', 'title']);

        $pickedProducts = empty($this->product_ids)
            ? collect()
            : Product::query()->whereIn('id', $this->product_ids)->get(['id', 'title'])->keyBy('id');

        return view('livewire.admin.collections.form', [
            'searchResults' => $searchResults,
            'pickedProducts' => $pickedProducts,
            'statuses' => CollectionStatus::cases(),
            'types' => CollectionType::cases(),
        ]);
    }
}
