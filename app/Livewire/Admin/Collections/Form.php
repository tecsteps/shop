<?php

namespace App\Livewire\Admin\Collections;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Collection;
use App\Models\Product;
use App\Support\HandleGenerator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public ?Collection $collection = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $productSearch = '';

    /** @var list<int> */
    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection && $collection->exists) {
            $this->authorize('update', $collection);

            $this->collection = $collection->load('products');

            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = (string) $collection->description_html;
            $this->status = $collection->status;
            $this->assignedProductIds = $collection->products
                ->sortBy('pivot.position')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            $this->authorize('create', Collection::class);
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->collection !== null;
    }

    #[Computed]
    public function searchResults(): SupportCollection
    {
        if (trim($this->productSearch) === '') {
            return collect();
        }

        return Product::query()
            ->where('title', 'like', '%'.trim($this->productSearch).'%')
            ->whereNotIn('id', $this->assignedProductIds)
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function assignedProducts(): SupportCollection
    {
        $products = Product::whereKey($this->assignedProductIds)->get()->keyBy('id');

        return collect($this->assignedProductIds)
            ->map(fn ($id) => $products->get($id))
            ->filter();
    }

    public function addProduct(int $productId): void
    {
        if (! in_array($productId, $this->assignedProductIds, true)) {
            $this->assignedProductIds[] = $productId;
        }

        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->assignedProductIds = array_values(array_diff($this->assignedProductIds, [$productId]));
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', Rule::unique('collections', 'handle')
                ->where('store_id', app('current_store')->id)
                ->ignore($this->collection?->id)],
            'descriptionHtml' => ['nullable', 'string'],
            'status' => ['required', 'in:active,draft,archived'],
        ]);

        $store = app('current_store');

        $data = [
            'title' => $this->title,
            'status' => $this->status,
            'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
        ];

        if ($this->handle !== '') {
            $data['handle'] = $this->handle;
        }

        $collection = $this->collection ?? new Collection(['store_id' => $store->id]);
        $collection->fill($data);

        if (empty($collection->handle)) {
            $collection->handle = app(HandleGenerator::class)->generate($this->title, 'collections', $store->id, $collection->id);
        }

        $collection->save();

        $sync = [];

        foreach ($this->assignedProductIds as $position => $productId) {
            $sync[$productId] = ['position' => $position];
        }

        $collection->products()->sync($sync);

        $this->toast('Collection saved');

        if ($this->collection === null) {
            $this->redirect(route('admin.collections.edit', $collection), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.admin.collections.form');
    }
}
