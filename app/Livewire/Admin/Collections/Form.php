<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Collection')]
class Form extends Component
{
    public ?Collection $collection = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        $this->collection = $collection?->exists ? $collection : null;

        if ($this->collection) {
            Gate::authorize('update', $collection);
            $this->fill(['title' => $collection->title, 'handle' => $collection->handle, 'descriptionHtml' => $collection->description_html ?? '', 'status' => $collection->status->value]);
            $this->assignedProductIds = $collection->products()->pluck('products.id')->all();
        } else {
            Gate::authorize('create', Collection::class);
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['nullable', 'string', 'max:255', Rule::unique('collections', 'handle')->where('store_id', app('current_store')->id)->ignore($this->collection?->id)],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'], 'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'assignedProductIds' => ['array'], 'assignedProductIds.*' => ['integer', Rule::exists('products', 'id')->where('store_id', app('current_store')->id)],
        ]);
        $data = ['store_id' => app('current_store')->id, 'title' => $validated['title'], 'handle' => $validated['handle'] ?: Str::slug($validated['title']), 'description_html' => $validated['descriptionHtml'], 'type' => 'manual', 'status' => $validated['status']];
        $this->collection ? $this->collection->update($data) : $this->collection = Collection::query()->create($data);
        $sync = collect($this->assignedProductIds)->values()->mapWithKeys(fn ($id, $position) => [$id => ['position' => $position]])->all();
        $this->collection->products()->sync($sync);
        session()->flash('toast', 'Collection saved.');
        $this->redirectRoute('admin.collections.edit', ['collection' => $this->collection], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.collections.form', ['products' => Product::query()->orderBy('title')->get()]);
    }
}
