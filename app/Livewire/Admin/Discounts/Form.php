<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('livewire.admin.layout.app')]
class Form extends Component
{
    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public ?string $valueAmount = null;

    public ?string $minimumPurchaseAmount = null;

    public ?string $usageLimit = null;

    public bool $onePerCustomer = false;

    public string $startsAt = '';

    public ?string $endsAt = null;

    public bool $isActive = true;

    public function mount(?Discount $discount = null): void
    {
        if ($discount && $discount->exists) {
            $this->discount = $discount;
            $this->type = $discount->type->value;
            $this->code = $discount->code ?? '';
            $this->valueType = $discount->value_type->value;
            $this->valueAmount = $discount->value_type === DiscountValueType::Percent
                ? (string) $discount->value_amount
                : (string) ($discount->value_amount / 100);
            $this->minimumPurchaseAmount = $discount->rules_json['minimum_purchase_amount'] ?? null;
            if ($this->minimumPurchaseAmount) {
                $this->minimumPurchaseAmount = (string) ($this->minimumPurchaseAmount / 100);
            }
            $this->usageLimit = $discount->usage_limit ? (string) $discount->usage_limit : null;
            $this->onePerCustomer = $discount->rules_json['one_per_customer'] ?? false;
            $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i') ?? '';
            $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
            $this->isActive = $discount->status === DiscountStatus::Active;
        } else {
            $this->startsAt = now()->format('Y-m-d\TH:i');
        }
    }

    public function generateCode(): void
    {
        $this->code = strtoupper(Str::random(8));
    }

    public function save(): void
    {
        $this->validate([
            'type' => 'required|in:code,automatic',
            'code' => $this->type === 'code' ? 'required|string|max:50' : 'nullable',
            'valueType' => 'required|in:percent,fixed,free_shipping',
            'startsAt' => 'required|date',
        ]);

        $valueAmount = $this->valueType === DiscountValueType::FreeShipping->value
            ? 0
            : ($this->valueType === 'percent'
                ? (int) $this->valueAmount
                : (int) round((float) ($this->valueAmount ?? 0) * 100));

        $rulesJson = [
            'minimum_purchase_amount' => $this->minimumPurchaseAmount
                ? (int) round((float) $this->minimumPurchaseAmount * 100)
                : null,
            'one_per_customer' => $this->onePerCustomer,
        ];

        $data = [
            'store_id' => session('store_id'),
            'type' => $this->type,
            'code' => $this->type === 'code' ? $this->code : null,
            'value_type' => $this->valueType,
            'value_amount' => $valueAmount,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt ?: null,
            'usage_limit' => $this->usageLimit ? (int) $this->usageLimit : null,
            'rules_json' => $rulesJson,
            'status' => $this->isActive ? DiscountStatus::Active : DiscountStatus::Disabled,
        ];

        if ($this->discount) {
            $this->discount->update($data);
        } else {
            $this->discount = Discount::withoutGlobalScopes()->create($data);
        }

        $this->dispatch('toast', type: 'success', message: 'Discount saved.');

        if ($this->discount->wasRecentlyCreated) {
            $this->redirect(route('admin.discounts.edit', $this->discount), navigate: true);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.discounts.form');
    }
}
