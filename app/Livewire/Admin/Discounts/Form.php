<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Shared discount form used by both the create and edit routes (roadmap step
 * 7.5 shared-form pattern).
 */
#[Layout('layouts::admin')]
class Form extends Component
{
    use AuthorizesRequests, SendsToasts;

    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public string $valueAmount = '';

    public string $minimumPurchaseAmount = '';

    /** @var list<int> */
    public array $specificProductIds = [];

    /** @var list<int> */
    public array $specificCollectionIds = [];

    public string $usageLimit = '';

    public bool $onePerCustomer = false;

    public string $startsAt = '';

    public string $endsAt = '';

    public bool $isActive = true;

    public string $productSearch = '';

    public string $collectionSearch = '';

    public function mount(?int $discountId = null): void
    {
        if ($discountId !== null) {
            $this->discount = Discount::query()->findOrFail($discountId);

            $this->authorize('view', $this->discount);
            $this->fillFromDiscount();

            return;
        }

        $this->authorize('create', Discount::class);
        $this->startsAt = now()->format('Y-m-d\TH:i');
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
        $this->specificProductIds = array_values(
            array_filter($this->specificProductIds, fn (int $id): bool => $id !== $productId),
        );
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
        $this->specificCollectionIds = array_values(
            array_filter($this->specificCollectionIds, fn (int $id): bool => $id !== $collectionId),
        );
    }

    public function save(): void
    {
        if ($this->isEditing) {
            $this->authorize('update', $this->discount);
        } else {
            $this->authorize('create', Discount::class);
        }

        $this->validate();

        $attributes = [
            'type' => $this->type,
            'code' => $this->type === DiscountType::Code->value ? strtoupper(trim($this->code)) : null,
            'value_type' => $this->valueType,
            'value_amount' => $this->resolvedValueAmount(),
            'starts_at' => Carbon::parse($this->startsAt),
            'ends_at' => trim($this->endsAt) !== '' ? Carbon::parse($this->endsAt) : null,
            'usage_limit' => trim($this->usageLimit) !== '' ? (int) $this->usageLimit : null,
            'rules_json' => $this->buildRules(),
            'status' => $this->isActive ? DiscountStatus::Active : DiscountStatus::Disabled,
        ];

        if ($this->isEditing) {
            $this->discount->update($attributes);
            $this->discount->refresh();
            $this->fillFromDiscount();
            $this->toast(__('Discount saved'));

            return;
        }

        $this->discount = Discount::query()->create($attributes);

        $this->flashToast(__('Discount saved'));
        $this->redirect(route('admin.discounts.edit', $this->discount), navigate: true);
    }

    public function deleteDiscount(): void
    {
        $this->authorize('delete', $this->discount);

        $this->discount->delete();

        $this->flashToast(__('Discount deleted.'));
        $this->redirect(route('admin.discounts.index'), navigate: true);
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->discount !== null;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    #[Computed]
    public function productSearchResults(): \Illuminate\Database\Eloquent\Collection
    {
        if (trim($this->productSearch) === '') {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return Product::query()
            ->where('title', 'like', '%'.trim($this->productSearch).'%')
            ->whereNotIn('id', $this->specificProductIds)
            ->orderBy('title')
            ->limit(8)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Collection>
     */
    #[Computed]
    public function collectionSearchResults(): \Illuminate\Database\Eloquent\Collection
    {
        if (trim($this->collectionSearch) === '') {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return Collection::query()
            ->where('title', 'like', '%'.trim($this->collectionSearch).'%')
            ->whereNotIn('id', $this->specificCollectionIds)
            ->orderBy('title')
            ->limit(8)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    #[Computed]
    public function selectedProducts(): \Illuminate\Database\Eloquent\Collection
    {
        return Product::query()->whereIn('id', $this->specificProductIds)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Collection>
     */
    #[Computed]
    public function selectedCollections(): \Illuminate\Database\Eloquent\Collection
    {
        return Collection::query()->whereIn('id', $this->specificCollectionIds)->get();
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.form')
            ->title($this->isEditing ? ($this->discount->code ?? __('Automatic discount')) : __('Create discount'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'type' => ['required', 'in:code,automatic'],
            'code' => [
                Rule::requiredIf($this->type === DiscountType::Code->value),
                'nullable',
                'string',
                'max:255',
                Rule::unique('discounts', 'code')
                    ->where('store_id', app('current_store')->getKey())
                    ->ignore($this->discount?->getKey()),
            ],
            'valueType' => ['required', 'in:percent,fixed,free_shipping'],
            'valueAmount' => [
                Rule::requiredIf($this->valueType !== DiscountValueType::FreeShipping->value),
                'nullable',
                'numeric',
                'min:0',
                ...($this->valueType === DiscountValueType::Percent->value ? ['max:100'] : []),
            ],
            'minimumPurchaseAmount' => ['nullable', 'numeric', 'min:0'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
        ];
    }

    /**
     * Percent values are stored as whole numbers, fixed values in minor
     * units, free shipping ignores the amount (spec 01 discounts notes).
     */
    protected function resolvedValueAmount(): int
    {
        return match (DiscountValueType::from($this->valueType)) {
            DiscountValueType::Percent => (int) round((float) $this->valueAmount),
            DiscountValueType::Fixed => (int) round((float) str_replace(',', '.', $this->valueAmount) * 100),
            DiscountValueType::FreeShipping => 0,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildRules(): array
    {
        $rules = [];

        if (trim($this->minimumPurchaseAmount) !== '') {
            $rules['min_purchase_amount'] = (int) round((float) str_replace(',', '.', $this->minimumPurchaseAmount) * 100);
        }

        if ($this->specificProductIds !== []) {
            $rules['applicable_product_ids'] = array_values($this->specificProductIds);
        }

        if ($this->specificCollectionIds !== []) {
            $rules['applicable_collection_ids'] = array_values($this->specificCollectionIds);
        }

        if ($this->onePerCustomer) {
            $rules['one_per_customer'] = true;
        }

        return $rules;
    }

    protected function fillFromDiscount(): void
    {
        $this->type = $this->discount->type->value;
        $this->code = (string) $this->discount->code;
        $this->valueType = $this->discount->value_type->value;
        $this->valueAmount = match ($this->discount->value_type) {
            DiscountValueType::Percent => (string) $this->discount->value_amount,
            DiscountValueType::Fixed => number_format($this->discount->value_amount / 100, 2, '.', ''),
            DiscountValueType::FreeShipping => '',
        };

        $minimum = $this->discount->minimumPurchaseAmount();
        $this->minimumPurchaseAmount = $minimum !== null ? number_format($minimum / 100, 2, '.', '') : '';

        $this->specificProductIds = array_map('intval', $this->discount->rules_json['applicable_product_ids'] ?? []);
        $this->specificCollectionIds = array_map('intval', $this->discount->rules_json['applicable_collection_ids'] ?? []);
        $this->onePerCustomer = (bool) ($this->discount->rules_json['one_per_customer'] ?? false);
        $this->usageLimit = $this->discount->usage_limit !== null ? (string) $this->discount->usage_limit : '';
        $this->startsAt = $this->discount->starts_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
        $this->endsAt = $this->discount->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->isActive = $this->discount->status === DiscountStatus::Active;
    }
}
