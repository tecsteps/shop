<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\Concerns\ResolvesAdminStore;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;

class Form extends Component
{
    use ResolvesAdminStore;

    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public string $valueAmount = '';

    public string $minimumPurchaseAmount = '';

    /** @var array<int> */
    public array $specificProductIds = [];

    /** @var array<int> */
    public array $specificCollectionIds = [];

    public string $usageLimit = '';

    public bool $onePerCustomer = false;

    public string $startsAt = '';

    public string $endsAt = '';

    public bool $isActive = true;

    public string $productSearch = '';

    public string $collectionSearch = '';

    public function mount(?Discount $discount = null): void
    {
        if ($discount !== null && $discount->exists) {
            abort_if($discount->store_id !== $this->store()->getKey(), 404);

            Gate::authorize('view', $discount);

            $this->discount = $discount;
            $this->type = $discount->type->value;
            $this->code = (string) $discount->code;
            $this->valueType = $discount->value_type->value;
            $this->valueAmount = $discount->value_type === DiscountValueType::Percent
                ? (string) $discount->value_amount
                : number_format($discount->value_amount / 100, 2, '.', '');

            $rules = $discount->rules_json ?? [];
            $minimum = $rules['min_purchase_amount'] ?? $rules['minimum_purchase_amount'] ?? null;
            $this->minimumPurchaseAmount = $minimum !== null ? number_format(((int) $minimum) / 100, 2, '.', '') : '';
            $this->specificProductIds = array_map('intval', (array) ($rules['applicable_product_ids'] ?? []));
            $this->specificCollectionIds = array_map('intval', (array) ($rules['applicable_collection_ids'] ?? []));
            $this->onePerCustomer = (bool) ($rules['once_per_customer'] ?? false);
            $this->usageLimit = $discount->usage_limit !== null ? (string) $discount->usage_limit : '';
            $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i') ?? '';
            $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i') ?? '';
            $this->isActive = $discount->status === DiscountStatus::Active;
        } else {
            Gate::authorize('create', Discount::class);

            $this->discount = null;
            $this->startsAt = now()->format('Y-m-d\TH:i');
        }
    }

    public function generateCode(): void
    {
        $this->code = strtoupper(Str::random(8));
    }

    public function addProduct(int $productId): void
    {
        if (! in_array($productId, $this->specificProductIds, true)) {
            $this->specificProductIds[] = $productId;
        }

        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->specificProductIds = array_values(array_filter(
            $this->specificProductIds,
            fn (int $id): bool => $id !== $productId
        ));
    }

    public function addCollection(int $collectionId): void
    {
        if (! in_array($collectionId, $this->specificCollectionIds, true)) {
            $this->specificCollectionIds[] = $collectionId;
        }

        $this->collectionSearch = '';
    }

    public function removeCollection(int $collectionId): void
    {
        $this->specificCollectionIds = array_values(array_filter(
            $this->specificCollectionIds,
            fn (int $id): bool => $id !== $collectionId
        ));
    }

    public function save(): mixed
    {
        if ($this->discount !== null) {
            Gate::authorize('update', $this->discount);
        } else {
            Gate::authorize('create', Discount::class);
        }

        $validated = $this->validate([
            'type' => 'required|in:code,automatic',
            'code' => 'required_if:type,code|nullable|string|max:50',
            'valueType' => 'required|in:percent,fixed,free_shipping',
            'valueAmount' => 'required_unless:valueType,free_shipping|nullable|numeric|min:0',
            'minimumPurchaseAmount' => 'nullable|numeric|min:0',
            'usageLimit' => 'nullable|integer|min:1',
            'startsAt' => 'required|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
        ]);

        if ($validated['type'] === 'code') {
            $exists = Discount::query()
                ->where('store_id', $this->store()->getKey())
                ->where('code', $validated['code'])
                ->when($this->discount !== null, fn ($query) => $query->whereKeyNot($this->discount->getKey()))
                ->exists();

            if ($exists) {
                $this->addError('code', 'The code has already been taken.');

                return null;
            }
        }

        $valueAmount = $validated['valueType'] === 'free_shipping'
            ? 0
            : ($validated['valueType'] === 'percent'
                ? (int) $validated['valueAmount']
                : (int) round(((float) $validated['valueAmount']) * 100));

        $rules = [];

        if ($validated['minimumPurchaseAmount'] !== '' && $validated['minimumPurchaseAmount'] !== null) {
            $rules['min_purchase_amount'] = (int) round(((float) $validated['minimumPurchaseAmount']) * 100);
        }

        if ($this->specificProductIds !== []) {
            $rules['applicable_product_ids'] = array_map('intval', $this->specificProductIds);
        }

        if ($this->specificCollectionIds !== []) {
            $rules['applicable_collection_ids'] = array_map('intval', $this->specificCollectionIds);
        }

        if ($this->onePerCustomer) {
            $rules['once_per_customer'] = true;
        }

        $data = [
            'type' => DiscountType::from($validated['type']),
            'code' => $validated['type'] === 'code' ? $validated['code'] : null,
            'value_type' => DiscountValueType::from($validated['valueType']),
            'value_amount' => $valueAmount,
            'starts_at' => $validated['startsAt'],
            'ends_at' => $validated['endsAt'] !== '' && $validated['endsAt'] !== null ? $validated['endsAt'] : null,
            'usage_limit' => $validated['usageLimit'] !== '' && $validated['usageLimit'] !== null ? (int) $validated['usageLimit'] : null,
            'rules_json' => $rules,
            'status' => $this->isActive ? DiscountStatus::Active : DiscountStatus::Disabled,
        ];

        if ($this->discount !== null) {
            $this->discount->update($data);
            $this->toast('success', 'Discount saved.');

            return null;
        }

        Discount::query()->create([
            'store_id' => $this->store()->getKey(),
            'usage_count' => 0,
            ...$data,
        ]);

        $this->flashToast('success', 'Discount saved.');

        return redirect()->route('admin.discounts.index');
    }

    public function render()
    {
        $storeId = $this->store()->getKey();

        $productResults = $this->productSearch !== ''
            ? Product::query()
                ->where('store_id', $storeId)
                ->whereNotIn('id', $this->specificProductIds)
                ->where('title', 'like', '%'.$this->productSearch.'%')
                ->orderBy('title')
                ->limit(10)
                ->get()
            : collect();

        $collectionResults = $this->collectionSearch !== ''
            ? Collection::query()
                ->where('store_id', $storeId)
                ->whereNotIn('id', $this->specificCollectionIds)
                ->where('title', 'like', '%'.$this->collectionSearch.'%')
                ->orderBy('title')
                ->limit(10)
                ->get()
            : collect();

        $selectedProducts = $this->specificProductIds === []
            ? collect()
            : Product::query()->where('store_id', $storeId)->whereIn('id', $this->specificProductIds)->orderBy('title')->get();

        $selectedCollections = $this->specificCollectionIds === []
            ? collect()
            : Collection::query()->where('store_id', $storeId)->whereIn('id', $this->specificCollectionIds)->orderBy('title')->get();

        return view('livewire.admin.discounts.form', [
            'productResults' => $productResults,
            'collectionResults' => $collectionResults,
            'selectedProducts' => $selectedProducts,
            'selectedCollections' => $selectedCollections,
            'isEditing' => $this->discount !== null,
        ])->layout('livewire.admin.layout.app', ['title' => $this->discount !== null ? ($this->discount->code ?? 'Automatic discount') : 'Create discount']);
    }
}
