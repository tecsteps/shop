<?php

namespace App\Livewire\Admin\Collections;

use App\Actions\SanitizeHtml;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Form extends Component
{
    public ?Collection $collection = null;

    public string $title = '';

    public string $handle = '';

    public bool $handleManuallyEdited = false;

    public string $descriptionHtml = '';

    public string $type = 'manual';

    public string $status = 'active';

    public string $productSearch = '';

    /** @var array<int, int|string> */
    public array $assignedProductIds = [];

    public function mount(?Collection $collection = null): void
    {
        if ($collection !== null && $collection->exists) {
            $this->authorize('update', $collection);

            $this->collection = $collection;
            $this->title = $collection->title;
            $this->handle = $collection->handle;
            $this->descriptionHtml = (string) ($collection->description_html ?? '');
            $this->type = $collection->type->value;
            $this->status = $collection->status->value;
            $this->assignedProductIds = $collection->products()->pluck('products.id')->all();
        } else {
            $this->authorize('create', Collection::class);
        }
    }

    /**
     * Auto-generate the URL handle from the title while the user has not
     * edited it manually (spec 03 §5.2).
     */
    public function updatedTitle(string $value): void
    {
        if (! $this->isEditing() && ! $this->handleManuallyEdited) {
            $this->handle = Str::slug($value);
        }
    }

    public function updatedHandle(): void
    {
        $this->handleManuallyEdited = true;
    }

    /**
     * Add a product to the collection.
     */
    public function addProduct(int $productId): void
    {
        if (! in_array($productId, array_map('intval', $this->assignedProductIds), true)) {
            $this->assignedProductIds[] = $productId;
        }

        $this->productSearch = '';
    }

    /**
     * Remove a product from the collection.
     */
    public function removeProduct(int $productId): void
    {
        $this->assignedProductIds = array_values(array_filter(
            $this->assignedProductIds,
            fn ($id): bool => (int) $id !== $productId,
        ));
    }

    /**
     * Move an assigned product one position up or down. Drag-and-drop is
     * intentionally replaced by buttons (see blade note, spec 03 §5.2).
     */
    public function moveProduct(int $index, string $direction): void
    {
        $ids = array_values($this->assignedProductIds);
        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($ids[$index], $ids[$swapWith])) {
            return;
        }

        [$ids[$index], $ids[$swapWith]] = [$ids[$swapWith], $ids[$index]];

        $this->assignedProductIds = $ids;
    }

    /**
     * Validate and save the collection with its product assignments
     * (spec 03 §5.2). Positions are the order of the assigned ids.
     */
    public function save(): void
    {
        $validated = $this->validate($this->rules());

        /** @var Store $store */
        $store = app('current_store');

        $data = [
            'title' => $validated['title'],
            'handle' => $validated['handle'],
            'description_html' => app(SanitizeHtml::class)($validated['descriptionHtml'] ?? null),
            'type' => $validated['type'],
            'status' => $validated['status'],
        ];

        $sync = [];
        foreach (array_map('intval', $this->assignedProductIds) as $position => $productId) {
            $sync[$productId] = ['position' => $position];
        }

        if ($this->isEditing()) {
            $this->authorize('update', $this->collection);

            $this->collection->update($data);
            $this->collection->products()->sync($sync);

            $this->dispatch('toast', type: 'success', message: 'Collection saved');
        } else {
            $this->authorize('create', Collection::class);

            $collection = Collection::create(array_merge($data, ['store_id' => $store->id]));
            $collection->products()->sync($sync);

            session()->flash('toast', ['type' => 'success', 'message' => 'Collection saved']);

            $this->redirect(route('admin.collections.edit', $collection));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.collections.form', [
            'searchResults' => $this->searchProducts(),
            'assignedProducts' => Product::query()
                ->whereIn('id', $this->assignedProductIds === [] ? [0] : $this->assignedProductIds)
                ->get()
                ->sortBy(fn (Product $product) => array_search($product->id, array_map('intval', $this->assignedProductIds), true))
                ->values(),
        ])->layout('admin.layouts.app')->title($this->isEditing() ? $this->collection->title : 'Create collection');
    }

    /**
     * Whether the form is editing an existing collection.
     */
    public function isEditing(): bool
    {
        return $this->collection !== null && $this->collection->exists;
    }

    /**
     * Validation rules (spec 03 §5.2).
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        /** @var Store $store */
        $store = app('current_store');

        return [
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'required', 'string', 'max:255',
                Rule::unique('collections', 'handle')
                    ->where('store_id', $store->id)
                    ->ignore($this->collection?->id),
            ],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'type' => ['required', Rule::in(['manual', 'automated'])],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            'assignedProductIds' => ['array'],
            'assignedProductIds.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $store->id)],
        ];
    }

    /**
     * Product search results for the picker, excluding assigned products.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    private function searchProducts(): \Illuminate\Database\Eloquent\Collection
    {
        if (trim($this->productSearch) === '') {
            return Product::query()->whereRaw('1 = 0')->get();
        }

        $term = '%'.addcslashes($this->productSearch, '\\%_').'%';

        return Product::query()
            ->where('title', 'like', $term)
            ->whereNotIn('id', array_map('intval', $this->assignedProductIds))
            ->orderBy('title')
            ->limit(8)
            ->get();
    }
}
