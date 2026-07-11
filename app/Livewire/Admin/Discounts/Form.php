<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\AdminComponent;
use App\Models\Discount;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

#[\Livewire\Attributes\Layout('layouts.admin')]
class Form extends AdminComponent
{
    public ?int $discountId = null;

    public string $code = '';

    public string $valueType = 'percent';

    public string $value = '10';

    public string $minimumPurchase = '0';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public ?int $usageLimit = null;

    public string $status = 'active';

    public function mount(?Discount $discount = null): void
    {
        if ($discount === null || ! $discount->exists) {
            Gate::authorize('create', Discount::class);

            return;
        }
        abort_unless($discount->store_id === $this->currentStore()->getKey(), 404);
        Gate::authorize('update', $discount);
        $this->discountId = $discount->getKey();
        $this->code = $discount->code ?? '';
        $this->valueType = $discount->value_type->value;
        $this->value = (string) $discount->value_amount;
        $this->minimumPurchase = (string) (($discount->rules_json['minimum_purchase_amount'] ?? 0) / 100);
        $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i');
        $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
        $this->usageLimit = $discount->usage_limit;
        $this->status = $discount->status->value;
    }

    public function save(): void
    {
        Gate::authorize($this->discountId === null ? 'create' : 'update', $this->discountId === null ? Discount::class : Discount::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($this->discountId));
        $validated = $this->validate([
            'code' => ['required', 'alpha_dash', 'max:100'], 'valueType' => ['required', Rule::enum(DiscountValueType::class)],
            'value' => ['required_unless:valueType,free_shipping', 'numeric', 'min:0'], 'minimumPurchase' => ['required', 'numeric', 'min:0'],
            'startsAt' => ['nullable', 'date'], 'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'usageLimit' => ['nullable', 'integer', 'min:1'], 'status' => ['required', Rule::enum(DiscountStatus::class)],
        ]);
        $discount = $this->discountId === null ? new Discount(['store_id' => $this->currentStore()->getKey()]) : Discount::query()->where('store_id', $this->currentStore()->getKey())->findOrFail($this->discountId);
        $discount->fill([
            'type' => 'code', 'code' => mb_strtoupper($validated['code']), 'value_type' => $validated['valueType'],
            'value_amount' => $validated['valueType'] === 'fixed' ? (int) round((float) $validated['value'] * 100) : (int) $validated['value'],
            'starts_at' => $validated['startsAt'], 'ends_at' => $validated['endsAt'], 'usage_limit' => $validated['usageLimit'],
            'status' => $validated['status'], 'rules_json' => ['minimum_purchase_amount' => (int) round((float) $validated['minimumPurchase'] * 100)],
        ])->save();
        $this->discountId = $discount->getKey();
        $this->toast('Discount saved.');
    }

    public function render()
    {
        return view('livewire.admin.discounts.form');
    }
}
