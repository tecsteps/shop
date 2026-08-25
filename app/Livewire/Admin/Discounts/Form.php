<?php

namespace App\Livewire\Admin\Discounts;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Form extends Component
{
    use DispatchesToasts;

    #[Layout('layouts.admin.app')]
    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public ?float $valueAmount = null;

    public ?float $minimumPurchaseAmount = null;

    /** @var list<int> */
    public array $specificProductIds = [];

    /** @var list<int> */
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
        if ($discount && $discount->exists) {
            $this->authorize('update', $discount);

            $this->discount = $discount;

            $this->type = $discount->type;
            $this->code = (string) $discount->code;
            $this->valueType = $discount->value_type;
            $this->valueAmount = $discount->value_amount !== null ? $discount->value_amount / 100 : null;
            $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i');
            $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
            $this->usageLimit = $discount->usage_limit;
            $this->isActive = $discount->status === 'active';

            $rules = $discount->rules_json ?? [];
            $this->minimumPurchaseAmount = isset($rules['min_purchase_amount']) ? (int) $rules['min_purchase_amount'] / 100 : null;
            $this->specificProductIds = array_map('intval', $rules['applicable_product_ids'] ?? []);
            $this->specificCollectionIds = array_map('intval', $rules['applicable_collection_ids'] ?? []);
        } else {
            $this->authorize('create', Discount::class);
            $this->startsAt = now()->format('Y-m-d\TH:i');
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->discount !== null;
    }

    #[Computed]
    public function productResults(): SupportCollection
    {
        if (trim($this->productSearch) === '') {
            return collect();
        }

        return Product::query()
            ->where('title', 'like', '%'.trim($this->productSearch).'%')
            ->whereNotIn('id', $this->specificProductIds)
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function collectionResults(): SupportCollection
    {
        if (trim($this->collectionSearch) === '') {
            return collect();
        }

        return Collection::query()
            ->where('title', 'like', '%'.trim($this->collectionSearch).'%')
            ->whereNotIn('id', $this->specificCollectionIds)
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function selectedProducts(): SupportCollection
    {
        return Product::whereKey($this->specificProductIds)->get();
    }

    #[Computed]
    public function selectedCollections(): SupportCollection
    {
        return Collection::whereKey($this->specificCollectionIds)->get();
    }

    public function generateCode(): void
    {
        $this->code = Str::upper(Str::random(8));
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
        $this->specificProductIds = array_values(array_diff($this->specificProductIds, [$productId]));
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
        $this->specificCollectionIds = array_values(array_diff($this->specificCollectionIds, [$collectionId]));
    }

    public function save(): void
    {
        $this->validate([
            'type' => ['required', 'in:code,automatic'],
            'code' => ['required_if:type,code', 'nullable', 'string', 'max:255'],
            'valueType' => ['required', 'in:percent,fixed,free_shipping'],
            'valueAmount' => ['required_unless:valueType,free_shipping', 'nullable', 'numeric', 'min:0'],
            'minimumPurchaseAmount' => ['nullable', 'numeric', 'min:0'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
        ]);

        $rules = [];

        if ($this->minimumPurchaseAmount !== null && $this->minimumPurchaseAmount > 0) {
            $rules['min_purchase_amount'] = (int) round($this->minimumPurchaseAmount * 100);
        }

        if ($this->specificProductIds !== []) {
            $rules['applicable_product_ids'] = $this->specificProductIds;
        }

        if ($this->specificCollectionIds !== []) {
            $rules['applicable_collection_ids'] = $this->specificCollectionIds;
        }

        $data = [
            'type' => $this->type,
            'code' => $this->type === 'code' ? Str::upper($this->code) : null,
            'value_type' => $this->valueType,
            'value_amount' => $this->valueType === 'free_shipping'
                ? null
                : (int) round(((float) ($this->valueAmount ?? 0)) * 100),
            'starts_at' => $this->startsAt ? Carbon::parse($this->startsAt) : null,
            'ends_at' => $this->endsAt ? Carbon::parse($this->endsAt) : null,
            'usage_limit' => $this->usageLimit,
            'rules_json' => $rules,
            'status' => $this->isActive ? 'active' : 'disabled',
        ];

        if ($this->discount === null) {
            $discount = new Discount([
                'store_id' => app('current_store')->id,
                'usage_count' => 0,
            ]);
        } else {
            $discount = $this->discount;
        }

        $discount->fill($data);
        $discount->save();

        $this->toast('Discount saved');

        if ($this->discount === null) {
            $this->redirect(route('admin.discounts.edit', $discount), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.admin.discounts.form');
    }
}
