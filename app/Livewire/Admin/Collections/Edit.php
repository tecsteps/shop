<?php

namespace App\Livewire\Admin\Collections;

use App\Enums\CollectionStatus;
use App\Enums\CollectionType;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Edit Collection')]
class Edit extends Component
{
    public Collection $collection;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $type = 'manual';

    public string $status = 'draft';

    public string $seoTitle = '';

    public string $seoDescription = '';

    public string $productSearch = '';

    /** @var array<int> */
    public array $productIds = [];

    public function mount(Collection $collection): void
    {
        $store = app('current_store');
        abort_unless($collection->store_id === $store->id, 404);

        $this->collection = $collection;
        $this->title = $collection->title;
        $this->handle = $collection->handle;
        $this->descriptionHtml = $collection->description_html ?? '';
        $this->type = $collection->type->value;
        $this->status = $collection->status->value;
        $this->productIds = $collection->products()->pluck('products.id')->toArray();
    }

    public function updatedTitle(): void
    {
        $this->handle = Str::slug($this->title);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'descriptionHtml' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', 'in:manual,automated'],
            'status' => ['required', 'in:draft,active,archived'],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $this->collection->update([
            'title' => $validated['title'],
            'handle' => $validated['handle'],
            'description_html' => $validated['descriptionHtml'],
            'type' => $validated['type'],
            'status' => $validated['status'],
        ]);

        $this->dispatch('toast', type: 'success', message: 'Collection updated.');
    }

    public function addProduct(int $productId): void
    {
        if (! in_array($productId, $this->productIds)) {
            $this->productIds[] = $productId;
            $this->collection->products()->syncWithoutDetaching([
                $productId => ['position' => count($this->productIds)],
            ]);
        }

        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->productIds = array_values(array_diff($this->productIds, [$productId]));
        $this->collection->products()->detach($productId);
    }

    public function render()
    {
        $store = app('current_store');

        $availableProducts = collect();
        if ($this->type === 'manual' && $this->productSearch !== '') {
            $availableProducts = Product::query()
                ->where('store_id', $store->id)
                ->where('title', 'like', '%'.$this->productSearch.'%')
                ->whereNotIn('id', $this->productIds)
                ->limit(10)
                ->get();
        }

        $assignedProducts = Product::query()
            ->whereIn('id', $this->productIds)
            ->get();

        return view('livewire.admin.collections.edit', [
            'statuses' => CollectionStatus::cases(),
            'types' => CollectionType::cases(),
            'availableProducts' => $availableProducts,
            'assignedProducts' => $assignedProducts,
        ]);
    }
}
