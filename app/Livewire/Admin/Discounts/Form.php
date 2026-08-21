<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public ?int $valueAmount = 10;

    public ?int $minimumPurchaseAmount = null;

    /** @var array<int, int> */
    public array $specificProductIds = [];

    /** @var array<int, int> */
    public array $specificCollectionIds = [];

    public ?int $usageLimit = null;

    public bool $onePerCustomer = false;

    public string $startsAt = '';

    public ?string $endsAt = null;

    public bool $isActive = true;

    public string $productSearch = '';

    public string $collectionSearch = '';

    public string $message = '';

    public function mount(?Discount $discount = null): void
    {
        $this->discount = $discount;
        $this->authorize($discount === null ? 'create' : 'view', $discount ?? Discount::class);
        $this->startsAt = now()->format('Y-m-d\TH:i');

        if ($discount !== null) {
            $rules = $discount->rules_json ?? [];
            $this->type = $discount->type instanceof DiscountType ? $discount->type->value : (string) $discount->type;
            $this->code = (string) ($discount->code ?? '');
            $this->valueType = $discount->value_type instanceof DiscountValueType ? $discount->value_type->value : (string) $discount->value_type;
            $this->valueAmount = $discount->value_amount;
            $this->minimumPurchaseAmount = $rules['min_purchase_amount'] ?? $rules['minimum_purchase_amount'] ?? null;
            $this->specificProductIds = array_map('intval', $rules['applicable_product_ids'] ?? []);
            $this->specificCollectionIds = array_map('intval', $rules['applicable_collection_ids'] ?? []);
            $this->usageLimit = $discount->usage_limit;
            $this->onePerCustomer = (bool) ($rules['one_per_customer'] ?? false);
            $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i') ?? '';
            $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
            $this->isActive = $discount->status === 'active';
        }
    }

    public function generateCode(): void
    {
        $this->code = Str::upper(Str::random(10));
    }

    public function addProduct(int $productId): void
    {
        $this->authorize($this->discount === null ? 'create' : 'update', $this->discount ?? Discount::class);
        $product = Product::query()->findOrFail($productId);

        if (! in_array($product->id, $this->specificProductIds, true)) {
            $this->specificProductIds[] = $product->id;
        }
        $this->productSearch = '';
    }

    public function removeProduct(int $productId): void
    {
        $this->authorize($this->discount === null ? 'create' : 'update', $this->discount ?? Discount::class);
        $this->specificProductIds = array_values(array_filter($this->specificProductIds, fn (int $id): bool => $id !== $productId));
    }

    public function addCollection(int $collectionId): void
    {
        $this->authorize($this->discount === null ? 'create' : 'update', $this->discount ?? Discount::class);
        $collection = Collection::query()->findOrFail($collectionId);

        if (! in_array($collection->id, $this->specificCollectionIds, true)) {
            $this->specificCollectionIds[] = $collection->id;
        }
        $this->collectionSearch = '';
    }

    public function removeCollection(int $collectionId): void
    {
        $this->authorize($this->discount === null ? 'create' : 'update', $this->discount ?? Discount::class);
        $this->specificCollectionIds = array_values(array_filter($this->specificCollectionIds, fn (int $id): bool => $id !== $collectionId));
    }

    public function save(): void
    {
        $data = $this->validate([
            'type' => ['required', 'in:code,automatic'],
            'code' => ['nullable', 'string', 'max:64', 'required_if:type,code', Rule::when($this->type === 'code', [Rule::unique('discounts', 'code')->where(fn ($query) => $query->where('store_id', app('current_store')->getKey()))->ignore($this->discount?->id)])],
            'valueType' => ['required', 'in:percent,fixed,free_shipping'],
            'valueAmount' => ['nullable', 'integer', 'min:0', 'required_unless:valueType,free_shipping'],
            'minimumPurchaseAmount' => ['nullable', 'integer', 'min:0'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'specificProductIds' => ['array'],
            'specificProductIds.*' => ['integer', 'exists:products,id'],
            'specificCollectionIds' => ['array'],
            'specificCollectionIds.*' => ['integer', 'exists:collections,id'],
            'onePerCustomer' => ['boolean'],
            'isActive' => ['boolean'],
        ]);

        if ($data['valueType'] === 'percent' && $data['valueAmount'] > 100) {
            $this->addError('valueAmount', 'Percentage discounts cannot exceed 100%.');

            return;
        }

        $ability = $this->discount === null ? 'create' : 'update';
        $this->authorize($ability, $this->discount ?? Discount::class);
        $rules = [
            'min_purchase_amount' => $data['minimumPurchaseAmount'],
            'applicable_product_ids' => array_map('intval', $data['specificProductIds']),
            'applicable_collection_ids' => array_map('intval', $data['specificCollectionIds']),
            'one_per_customer' => (bool) $data['onePerCustomer'],
        ];
        $attributes = [
            'store_id' => app('current_store')->getKey(),
            'code' => $data['type'] === 'code' ? Str::upper(trim($data['code'])) : null,
            'type' => $data['type'],
            'value_type' => $data['valueType'],
            'value_amount' => $data['valueAmount'] ?? 0,
            'status' => $data['isActive'] ? 'active' : 'disabled',
            'usage_limit' => $data['usageLimit'],
            'starts_at' => Carbon::parse($data['startsAt']),
            'ends_at' => $data['endsAt'] ? Carbon::parse($data['endsAt']) : null,
            'rules_json' => $rules,
        ];

        if ($this->discount === null) {
            $this->discount = Discount::create($attributes);
        } else {
            $this->discount->update($attributes);
        }

        $this->message = 'Discount saved.';
    }

    public function render(): mixed
    {
        $productResults = Product::query()
            ->select(['id', 'title'])
            ->when(trim($this->productSearch) !== '', fn ($query) => $query->where('title', 'like', '%'.trim($this->productSearch).'%'))
            ->whereNotIn('id', $this->specificProductIds ?: [0])
            ->limit(8)
            ->get();
        $collectionResults = Collection::query()
            ->select(['id', 'title'])
            ->when(trim($this->collectionSearch) !== '', fn ($query) => $query->where('title', 'like', '%'.trim($this->collectionSearch).'%'))
            ->whereNotIn('id', $this->specificCollectionIds ?: [0])
            ->limit(8)
            ->get();
        $selectedProducts = Product::query()->select(['id', 'title'])->whereIn('id', $this->specificProductIds ?: [0])->get();
        $selectedCollections = Collection::query()->select(['id', 'title'])->whereIn('id', $this->specificCollectionIds ?: [0])->get();

        return view('livewire.admin.discounts.form', compact('productResults', 'collectionResults', 'selectedProducts', 'selectedCollections'))->layout('layouts.admin');
    }
}
