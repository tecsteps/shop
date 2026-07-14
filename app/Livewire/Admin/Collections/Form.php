<?php

namespace App\Livewire\Admin\Collections;

use App\Livewire\Admin\AdminComponent;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Form extends AdminComponent
{
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
        if ($collection?->exists) {
            abort_unless((int) $collection->store_id === (int) $this->currentStore()->id, 404);
            $this->authorizeAction('update', $collection);
            $this->collection = $collection->load('products');
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = (string) $collection->description_html;
            $this->status = (string) $this->enumValue($collection->status);
            $this->assignedProductIds = $collection->products->modelKeys();
        } else {
            $this->authorizeAction('create', Collection::class);
        }
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->collection && $this->handle === '') {
            $this->handle = Str::slug($value);
        }
    }

    public function addProduct(int $productId): void
    {
        abort_unless(Product::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->whereKey($productId)->exists(), 404);
        if (! in_array($productId, $this->assignedProductIds, true)) {
            $this->assignedProductIds[] = $productId;
        }
        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->assignedProductIds = array_values(array_filter($this->assignedProductIds, fn (int $id): bool => $id !== $productId));
    }

    /** @param list<int> $order */
    public function reorderProducts(array $order): void
    {
        $allowed = array_flip($this->assignedProductIds);
        $this->assignedProductIds = array_values(array_filter(array_map('intval', $order), fn (int $id): bool => isset($allowed[$id])));
    }

    public function moveProduct(int $productId, string $direction): void
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 400);
        $index = array_search($productId, $this->assignedProductIds, true);
        abort_if($index === false, 404);
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if (! isset($this->assignedProductIds[$target])) {
            return;
        }
        [$this->assignedProductIds[$index], $this->assignedProductIds[$target]] = [$this->assignedProductIds[$target], $this->assignedProductIds[$index]];
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'alpha_dash', 'max:255', Rule::unique('collections', 'handle')->where('store_id', $this->currentStore()->id)->ignore($this->collection?->id)],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', Rule::in(['active', 'archived'])],
            'assignedProductIds' => ['array'],
            'assignedProductIds.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $this->currentStore()->id)],
        ]);
        $attributes = ['title' => $data['title'], 'handle' => Str::slug($data['handle']), 'description_html' => $data['descriptionHtml'] ?: null, 'status' => $data['status'], 'type' => 'manual'];
        if ($this->collection) {
            $this->authorizeAction('update', $this->collection);
            $this->collection->update($attributes);
        } else {
            $this->authorizeAction('create', Collection::class);
            $this->collection = Collection::query()->create([...$attributes, 'store_id' => $this->currentStore()->id]);
        }
        $this->collection->products()->sync(collect($data['assignedProductIds'])->mapWithKeys(fn ($id, $position) => [(int) $id => ['position' => $position]])->all());
        $this->toast('Collection saved successfully.');
        $this->redirect('/admin/collections/'.$this->collection->id.'/edit', navigate: true);
    }

    #[Computed]
    public function searchResults(): SupportCollection
    {
        if (mb_strlen(trim($this->productSearch)) < 2) {
            return collect();
        }

        return Product::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->where('title', 'like', '%'.trim($this->productSearch).'%')->whereNotIn('id', $this->assignedProductIds)->orderBy('title')->limit(8)->get();
    }

    #[Computed]
    public function assignedProducts(): SupportCollection
    {
        $items = Product::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->whereKey($this->assignedProductIds)->with('media')->get()->keyBy('id');

        return collect($this->assignedProductIds)->map(fn (int $id) => $items->get($id))->filter()->values();
    }

    public function render(): View
    {
        $label = $this->collection?->title ?: 'Add collection';

        return $this->admin(view('admin.collections.form'), $label, [['label' => 'Collections', 'url' => url('/admin/collections')], ['label' => $label]]);
    }
}
