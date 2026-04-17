<?php

namespace App\Livewire\Admin\Collections;

use App\Models\Collection;
use App\Models\Product;
use App\Support\HandleGenerator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Collection $collection = null;

    #[Validate('required|string|max:255')]
    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $type = 'manual';

    public array $productIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection?->exists) {
            $this->collection = $collection;
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = (string) $collection->description_html;
            $this->status = $collection->status->value;
            $this->type = $collection->type->value;
            $this->productIds = $collection->products()->pluck('products.id')->toArray();
        }
    }

    public function save(): mixed
    {
        $this->validate();

        $storeId = app('current_store')->id;
        $data = [
            'store_id' => $storeId,
            'title' => $this->title,
            'handle' => $this->handle ?: HandleGenerator::generate($this->title, 'collections', $storeId, $this->collection?->id),
            'description_html' => $this->descriptionHtml ?: null,
            'status' => $this->status,
            'type' => $this->type,
        ];

        if ($this->collection) {
            $this->collection->update($data);
        } else {
            $this->collection = Collection::create($data);
        }

        $this->collection->products()->sync($this->productIds);

        session()->flash('success', 'Collection saved.');

        return $this->redirect(route('admin.collections.edit', $this->collection), navigate: true);
    }

    public function render()
    {
        $availableProducts = Product::query()->orderBy('title')->get(['id', 'title', 'handle']);

        return view('livewire.admin.collections.form', compact('availableProducts'))
            ->title($this->collection ? 'Edit collection' : 'New collection');
    }
}
