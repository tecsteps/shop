<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?int $discountId = null;

    /** `code` discounts require a code; `automatic` discounts have no code. */
    public string $type = 'code';

    public string $title = '';

    public string $code = '';

    public string $valueType = 'percentage';

    public ?int $valueAmount = 10;

    public ?int $minimumPurchaseAmount = null;

    /** @var list<int> */
    public array $specificProductIds = [];

    /** @var list<int> */
    public array $specificCollectionIds = [];

    public ?int $usageLimit = null;

    public bool $onePerCustomer = false;

    public string $startsAt = '';

    public ?string $endsAt = null;

    public bool $isActive = true;

    public string $productSearch = '';

    public string $collectionSearch = '';

    public function mount(?int $discount = null): void
    {
        Gate::authorize('manage-discounts');
        $this->startsAt = now()->format('Y-m-d\TH:i');

        if ($discount === null) {
            return;
        }

        $saved = $this->discountQuery()->findOrFail($discount);
        $rules = $saved->rules_json ?? [];
        $this->discountId = $saved->id;
        $this->type = ($rules['activation_method'] ?? null) === 'automatic' || $saved->code === null ? 'automatic' : 'code';
        $this->title = $saved->title;
        $this->code = $saved->code ?? '';
        $this->valueType = $saved->type;
        $this->valueAmount = $saved->type === 'free_shipping' ? null : (int) $saved->value;
        $this->minimumPurchaseAmount = $saved->minimum_subtotal_amount;
        $this->specificProductIds = $this->existingIds($rules['product_ids'] ?? [], Product::class);
        $this->specificCollectionIds = $this->existingIds($rules['collection_ids'] ?? [], Collection::class);
        $this->usageLimit = $saved->usage_limit;
        $this->onePerCustomer = (bool) ($rules['one_per_customer'] ?? false);
        $this->startsAt = $saved->starts_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->endsAt = $saved->ends_at?->format('Y-m-d\TH:i');
        $this->isActive = $saved->is_active;
    }

    public function generateCode(): void
    {
        Gate::authorize('manage-discounts');

        do {
            $code = Str::upper(Str::random(10));
        } while ($this->discountQuery()->where('code', $code)->exists());

        $this->code = $code;
        $this->resetValidation('code');
    }

    public function addProduct(int $productId): void
    {
        Gate::authorize('manage-discounts');
        $product = Product::query()->where('store_id', $this->storeId())->findOrFail($productId);

        if (! in_array($product->id, $this->specificProductIds, true)) {
            $this->specificProductIds[] = $product->id;
        }

        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        Gate::authorize('manage-discounts');
        $this->specificProductIds = array_values(array_filter(
            $this->specificProductIds,
            static fn (int $selectedId): bool => $selectedId !== $productId,
        ));
    }

    public function addCollection(int $collectionId): void
    {
        Gate::authorize('manage-discounts');
        $collection = Collection::query()->where('store_id', $this->storeId())->findOrFail($collectionId);

        if (! in_array($collection->id, $this->specificCollectionIds, true)) {
            $this->specificCollectionIds[] = $collection->id;
        }

        $this->collectionSearch = '';
    }

    public function removeCollection(int $collectionId): void
    {
        Gate::authorize('manage-discounts');
        $this->specificCollectionIds = array_values(array_filter(
            $this->specificCollectionIds,
            static fn (int $selectedId): bool => $selectedId !== $collectionId,
        ));
    }

    public function save(): mixed
    {
        Gate::authorize('manage-discounts');

        $discount = $this->discountId === null
            ? null
            : $this->discountQuery()->findOrFail($this->discountId);

        $this->code = $this->type === 'code' ? Str::upper(trim($this->code)) : '';
        $valueRules = $this->valueType === 'free_shipping'
            ? ['nullable', 'integer']
            : ['required', 'integer', 'min:1'];
        if ($this->valueType === 'percentage') {
            $valueRules[] = 'max:100';
        }
        $uniqueCodeRule = Rule::unique('discounts', 'code')
            ->where(fn (QueryBuilder $query): QueryBuilder => $query->where('store_id', $this->storeId()));

        if ($discount !== null) {
            $uniqueCodeRule->ignore($discount);
        }

        $validated = $this->validate([
            'type' => ['required', Rule::in(['code', 'automatic'])],
            'title' => ['required', 'string', 'max:255'],
            'code' => [Rule::requiredIf($this->type === 'code'), 'nullable', 'string', 'max:64', $uniqueCodeRule],
            'valueType' => ['required', Rule::in(['percentage', 'fixed_amount', 'free_shipping'])],
            'valueAmount' => $valueRules,
            'minimumPurchaseAmount' => ['nullable', 'integer', 'min:0'],
            'specificProductIds' => ['array'],
            'specificProductIds.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $this->storeId())],
            'specificCollectionIds' => ['array'],
            'specificCollectionIds.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $this->storeId())],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'onePerCustomer' => ['boolean'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'isActive' => ['boolean'],
        ]);

        $rules = [
            'activation_method' => $validated['type'],
            'one_per_customer' => (bool) $validated['onePerCustomer'],
        ];

        if ($validated['specificProductIds'] !== []) {
            $rules['product_ids'] = array_values(array_unique(array_map('intval', $validated['specificProductIds'])));
        }

        if ($validated['specificCollectionIds'] !== []) {
            $rules['collection_ids'] = array_values(array_unique(array_map('intval', $validated['specificCollectionIds'])));
        }

        $attributes = [
            'store_id' => $this->storeId(),
            'title' => $validated['title'],
            'code' => $validated['type'] === 'code' ? $validated['code'] : null,
            'type' => $validated['valueType'],
            'value' => $validated['valueType'] === 'free_shipping' ? 0 : (int) $validated['valueAmount'],
            'minimum_subtotal_amount' => $validated['minimumPurchaseAmount'],
            'usage_limit' => $validated['usageLimit'],
            'starts_at' => $validated['startsAt'],
            'ends_at' => $validated['endsAt'],
            'is_active' => (bool) $validated['isActive'],
            'rules_json' => $rules,
        ];

        if ($discount === null) {
            $discount = Discount::create($attributes);
        } else {
            $discount->update($attributes);
        }

        session()->flash('status', 'Discount saved.');

        return $this->redirectRoute('admin.discounts');
    }

    public function render(): mixed
    {
        Gate::authorize('manage-discounts');
        $storeId = $this->storeId();

        return view('livewire.admin.discounts.form', [
            'isEditing' => $this->discountId !== null,
            'products' => $this->searchResults(Product::query()->where('store_id', $storeId), $this->productSearch),
            'collections' => $this->searchResults(Collection::query()->where('store_id', $storeId), $this->collectionSearch),
            'selectedProducts' => Product::query()->where('store_id', $storeId)->whereIn('id', $this->specificProductIds)->get(['id', 'title']),
            'selectedCollections' => Collection::query()->where('store_id', $storeId)->whereIn('id', $this->specificCollectionIds)->get(['id', 'title']),
        ])->layout('layouts.admin');
    }

    private function storeId(): int
    {
        $store = app()->bound('current_store') ? app('current_store') : null;
        abort_unless($store instanceof Store, 404);

        return (int) $store->getKey();
    }

    private function discountQuery(): Builder
    {
        return Discount::query()->where('store_id', $this->storeId());
    }

    /** @param array<int, mixed> $ids
     * @param  class-string<Product|Collection>  $model
     * @return list<int>
     */
    private function existingIds(array $ids, string $model): array
    {
        return $model::query()
            ->where('store_id', $this->storeId())
            ->whereIn('id', array_map('intval', $ids))
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();
    }

    /** @param Builder<Product>|Builder<Collection> $query
     * @return \Illuminate\Support\Collection<int, Product|Collection>
     */
    private function searchResults(Builder $query, string $search): \Illuminate\Support\Collection
    {
        if (mb_strlen(trim($search)) < 2) {
            return collect();
        }

        return $query->where('title', 'like', '%'.trim($search).'%')
            ->orderBy('title')
            ->limit(8)
            ->get(['id', 'title']);
    }
}
