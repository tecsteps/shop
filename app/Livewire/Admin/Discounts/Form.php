<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Collection as ProductCollection;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Store;
use App\Support\Money;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public string $valueAmount = '10';

    public string $minimumPurchaseAmount = '';

    /**
     * @var array<int, int>
     */
    public array $specificProductIds = [];

    /**
     * @var array<int, int>
     */
    public array $specificCollectionIds = [];

    public string $usageLimit = '';

    public bool $onePerCustomer = false;

    public string $startsAt = '';

    public string $endsAt = '';

    public bool $isActive = false;

    public string $productSearch = '';

    public string $collectionSearch = '';

    public string $storeCurrency = 'EUR';

    public function mount(?Discount $discount = null): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->storeCurrency = $store->default_currency;

        if ($discount?->exists) {
            abort_unless((int) $discount->store_id === $store->getKey(), 404);

            $this->authorize('update', $discount);

            $this->discount = $discount;
            $this->fillFromDiscount($discount);

            return;
        }

        $this->authorize('create', Discount::class);

        $this->startsAt = now()->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->authorizeSave();
        $this->normalizeCode();

        $this->validate([
            'type' => ['required', Rule::in(['code', 'automatic'])],
            'code' => [
                Rule::requiredIf($this->type === 'code'),
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($store): void {
                    if ($value === null || trim((string) $value) === '') {
                        return;
                    }

                    $exists = Discount::withoutGlobalScopes()
                        ->where('store_id', $store->getKey())
                        ->whereRaw('lower(code) = ?', [Str::lower(trim((string) $value))])
                        ->when($this->discount instanceof Discount, fn ($query) => $query->where('id', '!=', $this->discount?->getKey()))
                        ->exists();

                    if ($exists) {
                        $fail(__('The discount code has already been taken.'));
                    }
                },
            ],
            'valueType' => ['required', Rule::in(['percent', 'fixed', 'free_shipping'])],
            'valueAmount' => $this->valueAmountRules(),
            'minimumPurchaseAmount' => ['nullable', 'numeric', 'min:0'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'onePerCustomer' => ['boolean'],
            'isActive' => ['boolean'],
        ], [], [
            'valueAmount' => 'value amount',
        ]);

        $discount = $this->discount instanceof Discount
            ? tap($this->discount)->update($this->payload($store))
            : Discount::withoutGlobalScopes()->create($this->payload($store));

        $this->discount = $discount->refresh();
        $this->fillFromDiscount($this->discount);

        session()->flash('status', 'Discount saved');
        $this->dispatch('toast', type: 'success', message: __('Discount saved'));
    }

    public function generateCode(): void
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Discount::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->where('code', $code)
            ->exists());

        $this->code = $code;
    }

    public function addProduct(int $productId): void
    {
        abort_unless($this->productBelongsToStore($productId), 404);

        $this->specificProductIds = collect([...$this->specificProductIds, $productId])
            ->unique()
            ->values()
            ->all();
        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->specificProductIds = array_values(array_filter(
            $this->specificProductIds,
            fn (int $selectedProductId): bool => $selectedProductId !== $productId,
        ));
    }

    public function addCollection(int $collectionId): void
    {
        abort_unless($this->collectionBelongsToStore($collectionId), 404);

        $this->specificCollectionIds = collect([...$this->specificCollectionIds, $collectionId])
            ->unique()
            ->values()
            ->all();
        $this->collectionSearch = '';
    }

    public function removeCollection(int $collectionId): void
    {
        $this->specificCollectionIds = array_values(array_filter(
            $this->specificCollectionIds,
            fn (int $selectedCollectionId): bool => $selectedCollectionId !== $collectionId,
        ));
    }

    public function productResults(): SupportCollection
    {
        if (trim($this->productSearch) === '') {
            return collect();
        }

        return Product::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->whereNotIn('id', $this->specificProductIds)
            ->where('title', 'like', '%'.$this->productSearch.'%')
            ->orderBy('title')
            ->limit(5)
            ->get();
    }

    public function collectionResults(): SupportCollection
    {
        if (trim($this->collectionSearch) === '') {
            return collect();
        }

        return ProductCollection::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->whereNotIn('id', $this->specificCollectionIds)
            ->where('title', 'like', '%'.$this->collectionSearch.'%')
            ->orderBy('title')
            ->limit(5)
            ->get();
    }

    public function selectedProducts(): SupportCollection
    {
        return Product::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->whereKey($this->specificProductIds)
            ->orderBy('title')
            ->get();
    }

    public function selectedCollections(): SupportCollection
    {
        return ProductCollection::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->whereKey($this->specificCollectionIds)
            ->orderBy('title')
            ->get();
    }

    public function render(): mixed
    {
        return view('livewire.admin.discounts.form', [
            'isEditing' => $this->discount instanceof Discount,
            'productResults' => $this->productResults(),
            'collectionResults' => $this->collectionResults(),
            'selectedProducts' => $this->selectedProducts(),
            'selectedCollections' => $this->selectedCollections(),
        ])->layout('layouts.app', [
            'title' => $this->discount ? __('Edit discount') : __('Create discount'),
        ]);
    }

    private function fillFromDiscount(Discount $discount): void
    {
        $this->type = $discount->type->value;
        $this->code = (string) $discount->code;
        $this->valueType = $discount->value_type->value;
        $this->valueAmount = $discount->value_type === DiscountValueType::Fixed
            ? number_format($discount->value_amount / 100, 2, '.', '')
            : (string) $discount->value_amount;
        $this->minimumPurchaseAmount = data_get($discount->rules_json, 'min_purchase_amount')
            ? number_format(((int) data_get($discount->rules_json, 'min_purchase_amount')) / 100, 2, '.', '')
            : '';
        $this->specificProductIds = collect(data_get($discount->rules_json, 'applicable_product_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $this->specificCollectionIds = collect(data_get($discount->rules_json, 'applicable_collection_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
        $this->usageLimit = $discount->usage_limit !== null ? (string) $discount->usage_limit : '';
        $this->onePerCustomer = (bool) data_get($discount->rules_json, 'one_per_customer', false);
        $this->startsAt = $discount->starts_at->format('Y-m-d\TH:i');
        $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->isActive = $discount->status === DiscountStatus::Active;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Store $store): array
    {
        return [
            'store_id' => $store->getKey(),
            'type' => DiscountType::from($this->type),
            'code' => $this->type === 'code' ? Str::upper(trim($this->code)) : null,
            'value_type' => DiscountValueType::from($this->valueType),
            'value_amount' => $this->normalizedValueAmount(),
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt !== '' ? $this->endsAt : null,
            'usage_limit' => $this->usageLimit !== '' ? (int) $this->usageLimit : null,
            'rules_json' => $this->rulesPayload(),
            'status' => $this->statusForPayload(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesPayload(): array
    {
        return [
            'min_purchase_amount' => $this->minimumPurchaseAmount !== '' ? Money::fromDecimalString($this->minimumPurchaseAmount) : 0,
            'applicable_product_ids' => $this->validProductIds(),
            'applicable_collection_ids' => $this->validCollectionIds(),
            'one_per_customer' => $this->onePerCustomer,
            'customer_eligibility' => 'all',
        ];
    }

    private function normalizedValueAmount(): int
    {
        return match ($this->valueType) {
            'fixed' => Money::fromDecimalString($this->valueAmount),
            'free_shipping' => 0,
            default => (int) round((float) $this->valueAmount),
        };
    }

    /**
     * @return array<int, int>
     */
    private function validProductIds(): array
    {
        return Product::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->whereKey($this->specificProductIds)
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function validCollectionIds(): array
    {
        return ProductCollection::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->whereKey($this->specificCollectionIds)
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->all();
    }

    private function productBelongsToStore(int $productId): bool
    {
        return Product::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->whereKey($productId)
            ->exists();
    }

    private function collectionBelongsToStore(int $collectionId): bool
    {
        return ProductCollection::withoutGlobalScopes()
            ->where('store_id', $this->storeId())
            ->whereKey($collectionId)
            ->exists();
    }

    private function storeId(): int
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store->getKey();
    }

    private function authorizeSave(): void
    {
        if ($this->discount instanceof Discount) {
            $this->authorize('update', $this->discount);

            return;
        }

        $this->authorize('create', Discount::class);
    }

    private function normalizeCode(): void
    {
        if ($this->type !== DiscountType::Code->value) {
            $this->code = '';

            return;
        }

        $this->code = Str::upper(trim($this->code));
    }

    /**
     * @return list<mixed>
     */
    private function valueAmountRules(): array
    {
        return match ($this->valueType) {
            DiscountValueType::Percent->value => ['required', 'integer', 'min:1', 'max:100'],
            DiscountValueType::FreeShipping->value => ['nullable'],
            default => ['required', 'numeric', 'min:0.01'],
        };
    }

    private function statusForPayload(): DiscountStatus
    {
        if ($this->discount?->status === DiscountStatus::Expired) {
            return DiscountStatus::Expired;
        }

        if ($this->isActive) {
            return DiscountStatus::Active;
        }

        if ($this->discount?->status === DiscountStatus::Draft || ! $this->discount instanceof Discount) {
            return DiscountStatus::Draft;
        }

        return DiscountStatus::Disabled;
    }
}
