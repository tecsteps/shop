<?php

namespace App\Livewire\Admin\Collections;

use App\Enums\CollectionType;
use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Collection;
use App\Models\Product;
use App\Support\HandleGenerator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shared create/edit collection form with a live product picker and an ordered
 * assignment list (drag-to-reorder persists pivot positions on save).
 */
#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    use BindsCurrentStore;

    public ?Collection $collection = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $productSearch = '';

    /** @var array<int, int> Ordered product IDs in the collection. */
    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection !== null && $collection->exists) {
            $this->authorize('update', $collection);
            $this->collection = $collection;
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = (string) $collection->description_html;
            $this->status = $collection->status->value;
            $this->assignedProductIds = $collection->products()->pluck('products.id')->map(fn ($id): int => (int) $id)->all();
        } else {
            $this->authorize('create', Collection::class);
        }
    }

    public function getIsEditingProperty(): bool
    {
        return $this->collection !== null && $this->collection->exists;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function getSearchResultsProperty()
    {
        if ($this->productSearch === '') {
            return collect();
        }

        return Product::query()
            ->where('title', 'like', '%'.$this->productSearch.'%')
            ->whereNotIn('id', $this->assignedProductIds)
            ->limit(10)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function getAssignedProductsProperty()
    {
        if ($this->assignedProductIds === []) {
            return collect();
        }

        $products = Product::query()->with('media')->whereIn('id', $this->assignedProductIds)->get()->keyBy('id');

        return collect($this->assignedProductIds)
            ->map(fn (int $id) => $products->get($id))
            ->filter()
            ->values();
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
        $this->assignedProductIds = array_values(array_filter(
            $this->assignedProductIds,
            fn (int $id): bool => $id !== $productId,
        ));
    }

    /**
     * Reorder via an explicit ordered id list.
     *
     * @param  array<int, int>  $order
     */
    public function reorderProducts(array $order): void
    {
        $this->assignedProductIds = array_values(array_map('intval', $order));
    }

    /**
     * Drag-and-drop reorder handler for wire:sort: move the product to the new
     * zero-based position.
     */
    public function sortProducts(int $id, int $position): void
    {
        $ids = array_values(array_filter($this->assignedProductIds, fn (int $existing): bool => $existing !== $id));
        array_splice($ids, $position, 0, [$id]);
        $this->assignedProductIds = $ids;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
        ];
    }

    public function save(HandleGenerator $handles): mixed
    {
        $this->validate();

        $store = app('current_store');

        $handle = $this->handle !== ''
            ? $handles->generate($this->handle, 'collections', $store->id, $this->collection?->id)
            : $handles->generate($this->title, 'collections', $store->id, $this->collection?->id);

        $attributes = [
            'title' => $this->title,
            'handle' => $handle,
            'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
            'status' => $this->status,
            'type' => CollectionType::Manual->value,
        ];

        if ($this->isEditing) {
            $this->collection->update($attributes);
        } else {
            $this->collection = Collection::create($attributes);
        }

        $pivot = [];
        foreach (array_values($this->assignedProductIds) as $position => $id) {
            $pivot[$id] = ['position' => $position];
        }
        $this->collection->products()->sync($pivot);

        $this->dispatch('toast', type: 'success', message: __('Collection saved'));

        if (! $this->isEditing) {
            return $this->redirectRoute('admin.collections.edit', $this->collection, navigate: true);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.admin.collections.form');
    }
}
