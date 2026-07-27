<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

class Form extends Component
{
    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public ?int $valueAmount = null;

    public ?int $minimumPurchaseAmount = null;

    /** @var array<int, int|string> */
    public array $specificProductIds = [];

    /** @var array<int, int|string> */
    public array $specificCollectionIds = [];

    public ?int $usageLimit = null;

    public string $startsAt = '';

    public ?string $endsAt = null;

    public bool $isActive = true;

    public string $productSearch = '';

    public string $collectionSearch = '';

    public function mount(?Discount $discount = null): void
    {
        if ($discount !== null && $discount->exists) {
            $this->authorize('update', $discount);

            $this->discount = $discount;
            $this->loadFromDiscount($discount);
        } else {
            $this->authorize('create', Discount::class);

            $this->startsAt = now()->format('Y-m-d\TH:i');
        }
    }

    /**
     * Auto-generate a random discount code (spec 03 §10.2).
     */
    public function generateCode(): void
    {
        $this->code = mb_strtoupper(Str::random(10));
    }

    /**
     * Add a product to the applicability rules.
     */
    public function addProduct(int $productId): void
    {
        if (! in_array($productId, array_map('intval', $this->specificProductIds), true)) {
            $this->specificProductIds[] = $productId;
        }

        $this->productSearch = '';
    }

    /**
     * Remove a product from the applicability rules.
     */
    public function removeProduct(int $productId): void
    {
        $this->specificProductIds = array_values(array_filter(
            $this->specificProductIds,
            fn ($id): bool => (int) $id !== $productId,
        ));
    }

    /**
     * Add a collection to the applicability rules.
     */
    public function addCollection(int $collectionId): void
    {
        if (! in_array($collectionId, array_map('intval', $this->specificCollectionIds), true)) {
            $this->specificCollectionIds[] = $collectionId;
        }

        $this->collectionSearch = '';
    }

    /**
     * Remove a collection from the applicability rules.
     */
    public function removeCollection(int $collectionId): void
    {
        $this->specificCollectionIds = array_values(array_filter(
            $this->specificCollectionIds,
            fn ($id): bool => (int) $id !== $collectionId,
        ));
    }

    /**
     * Validate and save the discount (spec 03 §10.2, spec 05 §7).
     */
    public function save(): void
    {
        $this->normalizeNullableInputs();

        $validated = $this->validate($this->rules());

        /** @var Store $store */
        $store = app('current_store');

        $type = DiscountType::from($validated['type']);
        $code = $type === DiscountType::Code ? mb_strtoupper(trim($validated['code'])) : null;

        if ($code !== null) {
            $this->assertCodeIsUnique($store, $code);
        }

        $valueType = DiscountValueType::from($validated['valueType']);

        $data = [
            'type' => $type,
            'code' => $code,
            'value_type' => $valueType,
            'value_amount' => $valueType === DiscountValueType::FreeShipping ? 0 : (int) $validated['valueAmount'],
            'starts_at' => $validated['startsAt'],
            'ends_at' => $validated['endsAt'] ?? null,
            'usage_limit' => $validated['usageLimit'] ?? null,
            'rules_json' => array_merge($this->discount?->rules_json ?? [], [
                'min_purchase_amount' => $validated['minimumPurchaseAmount'] ?? null,
                'applicable_product_ids' => array_map('intval', $this->specificProductIds),
                'applicable_collection_ids' => array_map('intval', $this->specificCollectionIds),
            ]),
            'status' => $this->targetStatus(),
        ];

        if ($this->isEditing()) {
            $this->authorize('update', $this->discount);

            $this->discount->update($data);
            $this->discount->refresh();

            $this->dispatch('toast', type: 'success', message: 'Discount saved');
        } else {
            $this->authorize('create', Discount::class);

            $discount = Discount::create(array_merge($data, ['store_id' => $store->id]));

            session()->flash('toast', ['type' => 'success', 'message' => 'Discount saved']);

            $this->redirect(route('admin.discounts.edit', $discount));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.form', [
            'productResults' => $this->searchProducts(),
            'collectionResults' => $this->searchCollections(),
            'selectedProducts' => Product::query()->whereIn('id', $this->specificProductIds)->orderBy('title')->get(),
            'selectedCollections' => Collection::query()->whereIn('id', $this->specificCollectionIds)->orderBy('title')->get(),
        ])->layout('admin.layouts.app')->title($this->isEditing() ? ($this->discount->code ?? 'Automatic discount') : 'Create discount');
    }

    /**
     * Whether the form is editing an existing discount.
     */
    public function isEditing(): bool
    {
        return $this->discount !== null && $this->discount->exists;
    }

    /**
     * Validation rules (spec 03 §10.2, spec 05 §7).
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        /** @var Store $store */
        $store = app('current_store');

        return [
            'type' => ['required', Rule::in(['code', 'automatic'])],
            'code' => [$this->type === 'code' ? 'required' : 'nullable', 'string', 'max:255'],
            'valueType' => ['required', Rule::in(['percent', 'fixed', 'free_shipping'])],
            'valueAmount' => array_merge(
                [$this->valueType === 'free_shipping' ? 'nullable' : 'required', 'integer'],
                $this->valueType === 'percent' ? ['min:1', 'max:100'] : ['min:1'],
            ),
            'minimumPurchaseAmount' => ['nullable', 'integer', 'min:0'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'specificProductIds' => ['array'],
            'specificProductIds.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $store->id)],
            'specificCollectionIds' => ['array'],
            'specificCollectionIds.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $store->id)],
            'isActive' => ['boolean'],
        ];
    }

    /**
     * Convert empty-string optional inputs to null so "nullable|integer"
     * validates correctly from Livewire inputs.
     */
    private function normalizeNullableInputs(): void
    {
        foreach (['valueAmount', 'minimumPurchaseAmount', 'usageLimit'] as $property) {
            if ($this->{$property} === '' || $this->{$property} === null) {
                $this->{$property} = null;
            } else {
                $this->{$property} = (int) $this->{$property};
            }
        }

        if ($this->endsAt === '') {
            $this->endsAt = null;
        }
    }

    /**
     * Ensure the code is unique within the store, case-insensitively
     * (spec 05 §7.1).
     */
    private function assertCodeIsUnique(Store $store, string $code): void
    {
        $exists = Discount::query()
            ->where('store_id', $store->id)
            ->whereRaw('lower(code) = ?', [mb_strtolower($code)])
            ->when($this->isEditing(), fn (Builder $query) => $query->whereKeyNot($this->discount->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'code' => ["The code '{$code}' is already used by another discount in this store."],
            ]);
        }
    }

    /**
     * Next status given the current lifecycle state and the active toggle
     * (spec 05 §7 state machine; expired is terminal and automatic).
     */
    private function targetStatus(): DiscountStatus
    {
        if (! $this->isEditing()) {
            return $this->isActive ? DiscountStatus::Active : DiscountStatus::Draft;
        }

        return match ($this->discount->status) {
            DiscountStatus::Expired => DiscountStatus::Expired,
            DiscountStatus::Active => $this->isActive ? DiscountStatus::Active : DiscountStatus::Disabled,
            DiscountStatus::Draft => $this->isActive ? DiscountStatus::Active : DiscountStatus::Draft,
            DiscountStatus::Disabled => $this->isActive ? DiscountStatus::Active : DiscountStatus::Disabled,
        };
    }

    /**
     * Load the discount's data into the form properties (edit mode).
     */
    private function loadFromDiscount(Discount $discount): void
    {
        $rules = $discount->rules_json ?? [];

        $this->type = $discount->type->value;
        $this->code = (string) ($discount->code ?? '');
        $this->valueType = $discount->value_type->value;
        $this->valueAmount = $discount->value_type === DiscountValueType::FreeShipping ? null : $discount->value_amount;
        $this->minimumPurchaseAmount = $rules['min_purchase_amount'] ?? null;
        $this->specificProductIds = array_map('intval', $rules['applicable_product_ids'] ?? []);
        $this->specificCollectionIds = array_map('intval', $rules['applicable_collection_ids'] ?? []);
        $this->usageLimit = $discount->usage_limit;
        $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
        $this->isActive = $discount->status === DiscountStatus::Active;
    }

    /**
     * Product search results for the applicability picker.
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
            ->orderBy('title')
            ->limit(8)
            ->get();
    }

    /**
     * Collection search results for the applicability picker.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Collection>
     */
    private function searchCollections(): \Illuminate\Database\Eloquent\Collection
    {
        if (trim($this->collectionSearch) === '') {
            return Collection::query()->whereRaw('1 = 0')->get();
        }

        $term = '%'.addcslashes($this->collectionSearch, '\\%_').'%';

        return Collection::query()
            ->where('title', 'like', $term)
            ->orderBy('title')
            ->limit(8)
            ->get();
    }
}
