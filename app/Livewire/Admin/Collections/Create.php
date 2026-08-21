<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Create extends Component
{
    public string $title = '';

    public string $handle = '';

    public string $description = '';

    public string $status = 'draft';

    /** @var list<int> */
    public array $productIds = [];

    public function save(): void
    {
        $this->authorize('create', Collection::class);
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,active,archived'],
            'productIds' => ['array'],
        ]);
        $collection = Collection::query()->create([
            'title' => $data['title'],
            'handle' => $data['handle'] !== '' ? $data['handle'] : str($data['title'])->slug()->toString(),
            'description' => $data['description'],
            'status' => $data['status'],
        ]);
        $this->syncProducts($collection, $data['productIds']);
        $this->redirectRoute('admin.collections.edit', ['collection' => $collection], navigate: true);
    }

    /** @param list<int> $productIds */
    private function syncProducts(Collection $collection, array $productIds): void
    {
        $validIds = \App\Models\Product::query()->whereIn('id', $productIds)->pluck('id')->all();
        $collection->products()->sync(array_fill_keys($validIds, ['position' => 0]));
    }

    public function render(): View
    {
        return view('livewire.admin.collections.form', ['collection' => null])->layout('layouts.admin');
    }
}
