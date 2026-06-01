<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shared create/edit discount form. Persists the discount plus its rule set
 * (minimum purchase, product/collection restrictions) into rules_json, and
 * enforces per-store code uniqueness.
 */
#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    use BindsCurrentStore;

    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public ?string $valueAmount = null;

    public ?string $minimumPurchaseAmount = null;

    /** @var array<int, int> */
    public array $specificProductIds = [];

    /** @var array<int, int> */
    public array $specificCollectionIds = [];

    public ?int $usageLimit = null;

    public bool $onePerCustomer = false;

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public bool $isActive = true;

    public string $productSearch = '';

    public string $collectionSearch = '';

    public function mount(?Discount $discount = null): void
    {
        if ($discount !== null && $discount->exists) {
            $this->authorize('update', $discount);
            $this->discount = $discount;
            $this->fillFromDiscount();
        } else {
            $this->authorize('create', Discount::class);
            $this->startsAt = now()->format('Y-m-d\TH:i');
        }
    }

    private function fillFromDiscount(): void
    {
        $d = $this->discount;

        $this->type = $d->type->value;
        $this->code = (string) $d->code;
        $this->valueType = $d->value_type->value;
        $this->valueAmount = $d->value_type === DiscountValueType::Percent
            ? (string) $d->value_amount
            : ($d->value_amount !== null ? number_format($d->value_amount / 100, 2, '.', '') : null);
        $min = $d->minimumPurchaseAmount();
        $this->minimumPurchaseAmount = $min !== null ? number_format($min / 100, 2, '.', '') : null;
        $this->specificProductIds = $d->applicableProductIds();
        $this->specificCollectionIds = $d->applicableCollectionIds();
        $this->usageLimit = $d->usage_limit;
        $this->onePerCustomer = (bool) ($d->rules_json['one_per_customer'] ?? false);
        $this->startsAt = $d->starts_at?->format('Y-m-d\TH:i');
        $this->endsAt = $d->ends_at?->format('Y-m-d\TH:i');
        $this->isActive = $d->status === DiscountStatus::Active;
    }

    public function getIsEditingProperty(): bool
    {
        return $this->discount !== null && $this->discount->exists;
    }

    public function generateCode(): void
    {
        $this->code = Str::upper(Str::random(8));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function getProductResultsProperty()
    {
        if ($this->productSearch === '') {
            return collect();
        }

        return Product::query()
            ->where('title', 'like', '%'.$this->productSearch.'%')
            ->whereNotIn('id', $this->specificProductIds)
            ->limit(8)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Collection>
     */
    public function getCollectionResultsProperty()
    {
        if ($this->collectionSearch === '') {
            return collect();
        }

        return Collection::query()
            ->where('title', 'like', '%'.$this->collectionSearch.'%')
            ->whereNotIn('id', $this->specificCollectionIds)
            ->limit(8)
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    public function getSelectedProductsProperty()
    {
        return $this->specificProductIds === []
            ? collect()
            : Product::query()->whereIn('id', $this->specificProductIds)->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Collection>
     */
    public function getSelectedCollectionsProperty()
    {
        return $this->specificCollectionIds === []
            ? collect()
            : Collection::query()->whereIn('id', $this->specificCollectionIds)->get();
    }

    public function addProduct(int $id): void
    {
        if (! in_array($id, $this->specificProductIds, true)) {
            $this->specificProductIds[] = $id;
        }
        $this->productSearch = '';
    }

    public function removeProduct(int $id): void
    {
        $this->specificProductIds = array_values(array_filter($this->specificProductIds, fn (int $x): bool => $x !== $id));
    }

    public function addCollection(int $id): void
    {
        if (! in_array($id, $this->specificCollectionIds, true)) {
            $this->specificCollectionIds[] = $id;
        }
        $this->collectionSearch = '';
    }

    public function removeCollection(int $id): void
    {
        $this->specificCollectionIds = array_values(array_filter($this->specificCollectionIds, fn (int $x): bool => $x !== $id));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $storeId = app('current_store')->id;

        return [
            'type' => ['required', Rule::in(['code', 'automatic'])],
            'code' => [
                Rule::requiredIf($this->type === 'code'),
                'nullable',
                'string',
                'max:255',
                Rule::unique('discounts', 'code')
                    ->where('store_id', $storeId)
                    ->ignore($this->discount?->id),
            ],
            'valueType' => ['required', Rule::in(['percent', 'fixed', 'free_shipping'])],
            'valueAmount' => [Rule::requiredIf($this->valueType !== 'free_shipping'), 'nullable', 'numeric', 'min:0'],
            'minimumPurchaseAmount' => ['nullable', 'numeric', 'min:0'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
        ];
    }

    public function save(): mixed
    {
        $this->validate();

        $valueAmount = match ($this->valueType) {
            'percent' => (int) $this->valueAmount,
            'fixed' => (int) round(((float) $this->valueAmount) * 100),
            default => 0,
        };

        $rules = [];
        if ($this->minimumPurchaseAmount !== null && $this->minimumPurchaseAmount !== '') {
            $rules['min_purchase_amount'] = (int) round(((float) $this->minimumPurchaseAmount) * 100);
        }
        if ($this->specificProductIds !== []) {
            $rules['applicable_product_ids'] = array_values($this->specificProductIds);
        }
        if ($this->specificCollectionIds !== []) {
            $rules['applicable_collection_ids'] = array_values($this->specificCollectionIds);
        }
        $rules['one_per_customer'] = $this->onePerCustomer;

        $attributes = [
            'type' => $this->type,
            'code' => $this->type === 'code' ? $this->code : null,
            'value_type' => $this->valueType,
            'value_amount' => $valueAmount,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt !== '' ? $this->endsAt : null,
            'usage_limit' => $this->usageLimit,
            'rules_json' => $rules,
            'status' => $this->isActive ? DiscountStatus::Active->value : DiscountStatus::Disabled->value,
        ];

        if ($this->isEditing) {
            $this->discount->update($attributes);
        } else {
            $attributes['usage_count'] = 0;
            $this->discount = Discount::create($attributes);
        }

        $this->dispatch('toast', type: 'success', message: __('Discount saved'));

        if (! $this->isEditing) {
            return $this->redirectRoute('admin.discounts.edit', $this->discount, navigate: true);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.admin.discounts.form');
    }
}
