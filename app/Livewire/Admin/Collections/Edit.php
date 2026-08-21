<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Edit extends Component
{
    public Collection $collection;

    public string $title = '';

    public string $handle = '';

    public string $description = '';

    public string $status = 'draft';

    /** @var list<int> */
    public array $productIds = [];

    public function mount(Collection $collection): void
    {
        $this->collection = $collection;
        $this->title = $collection->title;
        $this->handle = $collection->handle;
        $this->description = (string) $collection->description;
        $this->status = $collection->status->value;
        $this->productIds = $collection->products()->pluck('products.id')->all();
    }

    public function save(): void
    {
        $this->authorize('update', $this->collection);
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,archived'],
            'productIds' => ['array'],
        ]);
        $this->collection->update([
            'title' => $data['title'],
            'handle' => $data['handle'],
            'description' => $data['description'],
            'status' => $data['status'],
        ]);
        $validIds = \App\Models\Product::query()->whereIn('id', $data['productIds'])->pluck('id')->all();
        $this->collection->products()->sync(array_fill_keys($validIds, ['position' => 0]));
        $this->dispatch('toast', message: 'Collection saved.');
    }

    public function render(): View
    {
        return view('livewire.admin.collections.form')->layout('layouts.admin');
    }
}
