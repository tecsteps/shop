<?php

namespace App\Livewire\Admin\Discounts;

use App\Livewire\Admin\AdminComponent;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;

class Form extends AdminComponent
{
    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

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

    public function mount(?Discount $discount = null): void
    {
        if ($discount?->exists) {
            abort_unless((int) $discount->store_id === (int) $this->currentStore()->id, 404);
            $this->authorizeAction('update', $discount);
            $this->discount = $discount;
            $rules = (array) $discount->rules_json;
            $this->type = (string) $this->enumValue($discount->type);
            $this->code = (string) $discount->code;
            $this->valueType = (string) $this->enumValue($discount->value_type);
            $this->valueAmount = (int) $discount->value_amount;
            $this->minimumPurchaseAmount = data_get($rules, 'min_purchase_amount');
            $this->specificProductIds = array_map('intval', (array) data_get($rules, 'applicable_product_ids', []));
            $this->specificCollectionIds = array_map('intval', (array) data_get($rules, 'applicable_collection_ids', []));
            $this->usageLimit = $discount->usage_limit;
            $this->onePerCustomer = (bool) data_get($rules, 'one_per_customer', false);
            $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i');
            $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
            $this->isActive = $this->enumValue($discount->status) === 'active';
        } else {
            $this->authorizeAction('create', Discount::class);
            $this->startsAt = now()->format('Y-m-d\TH:i');
        }
    }

    public function generateCode(): void
    {
        $this->code = Str::upper(Str::random(10));
    }

    public function addProduct(int $id): void
    {
        abort_unless(Product::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->whereKey($id)->exists(), 404);
        if (! in_array($id, $this->specificProductIds, true)) {
            $this->specificProductIds[] = $id;
        } $this->productSearch = '';
    }

    public function removeProduct(int $id): void
    {
        $this->specificProductIds = array_values(array_diff($this->specificProductIds, [$id]));
    }

    public function addCollection(int $id): void
    {
        abort_unless(Collection::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->whereKey($id)->exists(), 404);
        if (! in_array($id, $this->specificCollectionIds, true)) {
            $this->specificCollectionIds[] = $id;
        } $this->collectionSearch = '';
    }

    public function removeCollection(int $id): void
    {
        $this->specificCollectionIds = array_values(array_diff($this->specificCollectionIds, [$id]));
    }

    public function save(): void
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(['code', 'automatic'])],
            'code' => [Rule::requiredIf($this->type === 'code'), 'nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($this->type !== 'code' || blank($value)) {
                    return;
                }

                $exists = Discount::query()
                    ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim((string) $value))])
                    ->when($this->discount, fn ($query) => $query->whereKeyNot($this->discount->id))
                    ->exists();
                if ($exists) {
                    $fail('This discount code is already in use.');
                }
            }],
            'valueType' => ['required', Rule::in(['percent', 'fixed', 'free_shipping'])],
            'valueAmount' => [Rule::requiredIf($this->valueType !== 'free_shipping'), 'nullable', 'integer', 'min:0', $this->valueType === 'percent' ? 'max:100' : 'max:999999999'],
            'minimumPurchaseAmount' => ['nullable', 'integer', 'min:0'],
            'specificProductIds.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $this->currentStore()->id)],
            'specificCollectionIds.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $this->currentStore()->id)],
            'usageLimit' => ['nullable', 'integer', 'min:1'], 'onePerCustomer' => ['boolean'],
            'startsAt' => ['required', 'date'], 'endsAt' => ['nullable', 'date', 'after:startsAt'], 'isActive' => ['boolean'],
        ]);
        $attributes = [
            'type' => $validated['type'], 'code' => $validated['type'] === 'code' ? Str::upper(trim($validated['code'])) : null,
            'value_type' => $validated['valueType'], 'value_amount' => $validated['valueType'] === 'free_shipping' ? 0 : (int) $validated['valueAmount'],
            'starts_at' => $validated['startsAt'], 'ends_at' => $validated['endsAt'] ?: null, 'usage_limit' => $validated['usageLimit'],
            'rules_json' => ['min_purchase_amount' => $validated['minimumPurchaseAmount'], 'applicable_product_ids' => array_map('intval', $this->specificProductIds), 'applicable_collection_ids' => array_map('intval', $this->specificCollectionIds), 'one_per_customer' => $validated['onePerCustomer']],
            'status' => $validated['isActive'] ? 'active' : 'disabled',
        ];
        if ($this->discount) {
            $this->authorizeAction('update', $this->discount);
            $this->discount->update($attributes);
        } else {
            $this->authorizeAction('create', Discount::class);
            $this->discount = Discount::query()->create([...$attributes, 'store_id' => $this->currentStore()->id, 'usage_count' => 0]);
        }
        $this->toast('Discount saved successfully.');
        $this->redirect('/admin/discounts/'.$this->discount->id.'/edit', navigate: true);
    }

    #[Computed]
    public function productResults(): SupportCollection
    {
        return mb_strlen(trim($this->productSearch)) < 2 ? collect() : Product::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->where('title', 'like', '%'.$this->productSearch.'%')->whereNotIn('id', $this->specificProductIds)->limit(8)->get();
    }

    #[Computed]
    public function collectionResults(): SupportCollection
    {
        return mb_strlen(trim($this->collectionSearch)) < 2 ? collect() : Collection::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->where('title', 'like', '%'.$this->collectionSearch.'%')->whereNotIn('id', $this->specificCollectionIds)->limit(8)->get();
    }

    #[Computed]
    public function selectedProducts(): SupportCollection
    {
        return Product::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->whereKey($this->specificProductIds)->orderBy('title')->get();
    }

    #[Computed]
    public function selectedCollections(): SupportCollection
    {
        return Collection::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->whereKey($this->specificCollectionIds)->orderBy('title')->get();
    }

    public function render(): View
    {
        $label = $this->discount?->code ?: ($this->discount ? 'Automatic discount' : 'Create discount');

        return $this->admin(view('admin.discounts.form'), $label, [['label' => 'Discounts', 'url' => url('/admin/discounts')], ['label' => $label]]);
    }
}
