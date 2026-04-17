<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use App\Support\HandleGenerator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Collection $collection = null;

    public string $mode = 'create';

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:255')]
    public string $handle = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|string|in:manual,smart')]
    public string $type = 'manual';

    #[Validate('required|string|in:draft,active')]
    public string $status = 'draft';

    /** @var array<int, int> */
    public array $productIds = [];

    public string $productSearch = '';

    public function mount(?Collection $collection = null): void
    {
        if ($collection !== null && $collection->exists) {
            $this->collection = $collection;
            $this->mode = 'edit';
            $this->title = (string) $collection->title;
            $this->handle = (string) $collection->handle;
            $this->description = (string) ($collection->description_html ?? '');
            $this->type = $collection->type->value;
            $this->status = $collection->status->value;
            $this->productIds = $collection->products()->pluck('products.id')->map(fn ($id) => (int) $id)->all();
        }
    }

    public function addProduct(int $productId): void
    {
        if (! in_array($productId, $this->productIds, true)) {
            $this->productIds[] = $productId;
        }
    }

    public function removeProduct(int $productId): void
    {
        $this->productIds = array_values(array_filter($this->productIds, fn (int $id): bool => $id !== $productId));
    }

    public function save(): mixed
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        if ($this->mode === 'create') {
            $handle = $this->handle !== ''
                ? HandleGenerator::generate($this->handle, 'collections', $store->id)
                : HandleGenerator::generate($this->title, 'collections', $store->id);

            $collection = Collection::create([
                'store_id' => $store->id,
                'title' => $this->title,
                'handle' => $handle,
                'description_html' => $this->description !== '' ? $this->description : null,
                'type' => $this->type,
                'status' => $this->status,
            ]);
        } else {
            $collection = $this->collection;
            $handle = $this->handle !== '' && $this->handle !== $collection->handle
                ? HandleGenerator::generate($this->handle, 'collections', $store->id, $collection->id)
                : $collection->handle;

            $collection->update([
                'title' => $this->title,
                'handle' => $handle,
                'description_html' => $this->description !== '' ? $this->description : null,
                'type' => $this->type,
                'status' => $this->status,
            ]);
        }

        $syncData = [];
        foreach ($this->productIds as $position => $id) {
            $syncData[$id] = ['position' => $position];
        }
        $collection->products()->sync($syncData);

        session()->flash('status', 'Collection saved.');

        return redirect()->route('admin.collections.index');
    }

    public function render(): View
    {
        $searchResults = $this->productSearch !== ''
            ? Product::query()
                ->where('title', 'like', '%'.$this->productSearch.'%')
                ->whereNotIn('id', $this->productIds)
                ->limit(10)
                ->get()
            : collect();

        $assignedProducts = $this->productIds === []
            ? collect()
            : Product::query()->whereIn('id', $this->productIds)->get();

        return view('livewire.admin.collections.form', [
            'searchResults' => $searchResults,
            'assignedProducts' => $assignedProducts,
        ]);
    }
}
