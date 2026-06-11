<?php

namespace App\Livewire\Admin\Collections;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shared collection form used by both the create and edit routes (roadmap
 * step 7.5 shared-form pattern): create mode when no collection id is bound,
 * edit mode otherwise.
 */
#[Layout('layouts::admin')]
class Form extends Component
{
    use AuthorizesRequests, SendsToasts;

    public ?Collection $collection = null;

    public string $title = '';

    public string $handle = '';

    public string $descriptionHtml = '';

    public string $status = 'active';

    public string $productSearch = '';

    /**
     * Ordered list of product ids assigned to this collection. The order of
     * this array is the collection_products position order.
     *
     * @var list<int>
     */
    public array $assignedProductIds = [];

    public function mount(?int $collectionId = null): void
    {
        if ($collectionId !== null) {
            $this->collection = Collection::query()->findOrFail($collectionId);

            $this->authorize('view', $this->collection);
            $this->fillFromCollection();

            return;
        }

        $this->authorize('create', Collection::class);
    }

    public function addProduct(int $productId): void
    {
        if (in_array($productId, $this->assignedProductIds, true)) {
            return;
        }

        Product::query()->findOrFail($productId);

        $this->assignedProductIds[] = $productId;
        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->assignedProductIds = array_values(
            array_filter($this->assignedProductIds, fn (int $id): bool => $id !== $productId),
        );
    }

    /**
     * Drag-to-reorder handler (wire:sort): move a product to a position.
     */
    public function reorderProducts(int $productId, int $position): void
    {
        $currentIndex = array_search($productId, $this->assignedProductIds, true);

        if ($currentIndex === false) {
            return;
        }

        array_splice($this->assignedProductIds, $currentIndex, 1);
        array_splice($this->assignedProductIds, $position, 0, [$productId]);
    }

    public function save(): void
    {
        if ($this->isEditing) {
            $this->authorize('update', $this->collection);
        } else {
            $this->authorize('create', Collection::class);
        }

        $this->validate();

        $isCreating = ! $this->isEditing;

        DB::transaction(function (): void {
            $attributes = [
                'title' => $this->title,
                'handle' => $this->resolvedHandle(),
                'description_html' => $this->descriptionHtml !== '' ? $this->descriptionHtml : null,
                'status' => $this->status,
            ];

            if ($this->isEditing) {
                $this->collection->update($attributes);
            } else {
                $this->collection = Collection::query()->create($attributes + ['type' => 'manual']);
            }

            $this->syncProducts();
        });

        if ($isCreating) {
            $this->flashToast(__('Collection saved'));
            $this->redirect(route('admin.collections.edit', $this->collection), navigate: true);

            return;
        }

        $this->collection->refresh();
        $this->fillFromCollection();
        $this->toast(__('Collection saved'));
    }

    public function deleteCollection(): void
    {
        $this->authorize('delete', $this->collection);

        $this->collection->products()->detach();
        $this->collection->delete();

        $this->flashToast(__('Collection deleted.'));
        $this->redirect(route('admin.collections.index'), navigate: true);
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->collection !== null;
    }

    /**
     * Products matching the search input that are not already assigned.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    #[Computed]
    public function searchResults(): \Illuminate\Database\Eloquent\Collection
    {
        if (trim($this->productSearch) === '') {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return Product::query()
            ->where('title', 'like', '%'.trim($this->productSearch).'%')
            ->whereNotIn('id', $this->assignedProductIds)
            ->orderBy('title')
            ->limit(8)
            ->get();
    }

    /**
     * Assigned products in their current display order.
     *
     * @return \Illuminate\Support\Collection<int, Product>
     */
    #[Computed]
    public function assignedProducts(): \Illuminate\Support\Collection
    {
        $products = Product::query()
            ->with(['media' => fn ($query) => $query->limit(1)])
            ->whereIn('id', $this->assignedProductIds)
            ->get()
            ->keyBy('id');

        return collect($this->assignedProductIds)
            ->map(fn (int $id): ?Product => $products->get($id))
            ->filter()
            ->values();
    }

    public function render(): View
    {
        return view('livewire.admin.collections.form')
            ->title($this->isEditing ? $this->collection->title : __('Add collection'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'handle' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('collections', 'handle')
                    ->where('store_id', app('current_store')->getKey())
                    ->ignore($this->collection?->getKey()),
            ],
            'descriptionHtml' => ['nullable', 'string', 'max:65535'],
            'status' => ['required', 'in:draft,active,archived'],
        ];
    }

    protected function syncProducts(): void
    {
        $validIds = Product::query()
            ->whereIn('id', $this->assignedProductIds)
            ->pluck('id')
            ->all();

        $sync = [];

        foreach (array_values(array_intersect($this->assignedProductIds, $validIds)) as $position => $productId) {
            $sync[$productId] = ['position' => $position];
        }

        $this->collection->products()->sync($sync);
    }

    protected function resolvedHandle(): string
    {
        $handle = trim($this->handle) !== '' ? trim($this->handle) : $this->title;

        return Str::slug($handle);
    }

    protected function fillFromCollection(): void
    {
        $this->title = $this->collection->title;
        $this->handle = $this->collection->handle;
        $this->descriptionHtml = (string) $this->collection->description_html;
        $this->status = $this->collection->status->value;
        $this->assignedProductIds = $this->collection->products()->pluck('products.id')->all();
    }
}
