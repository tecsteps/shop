<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Discount')]
class Form extends Component
{
    public ?Discount $discount = null;

    #[Validate('required|string|in:code,automatic')]
    public string $type = 'code';

    #[Validate('nullable|string|max:255')]
    public string $code = '';

    #[Validate('required|string|in:percent,fixed,free_shipping')]
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
            $this->authorize('update', $discount);
            $this->discount = $discount;
            $this->type = $discount->type->value;
            $this->code = $discount->code ?? '';
            $this->valueType = $discount->value_type->value;
            $this->valueAmount = $discount->value_type === DiscountValueType::Percent
                ? (string) $discount->value_amount
                : (string) ($discount->value_amount / 100);
            $this->usageLimit = $discount->usage_limit ? (string) $discount->usage_limit : null;
            $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i') ?? '';
            $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
            $this->isActive = $discount->status === DiscountStatus::Active;

            $rules = $discount->rules_json ?? [];
            $this->minimumPurchaseAmount = isset($rules['minimum_purchase_amount'])
                ? (string) ($rules['minimum_purchase_amount'] / 100)
                : null;
            $this->onePerCustomer = $rules['one_per_customer'] ?? false;
        } else {
            $this->startsAt = now()->format('Y-m-d\TH:i');
        }
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->discount !== null && $this->discount->exists;
    }

    public function generateCode(): void
    {
        $this->code = Str::upper(Str::random(8));
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing) {
            $this->authorize('update', $this->discount);
        } else {
            $this->authorize('create', Discount::class);
        }

        $store = app('current_store');

        $valueAmount = match ($this->valueType) {
            'percent' => (int) ($this->valueAmount ?? 0),
            'fixed' => (int) round((float) ($this->valueAmount ?? 0) * 100),
            default => 0,
        };

        $rules = [];
        if ($this->minimumPurchaseAmount) {
            $rules['minimum_purchase_amount'] = (int) round((float) $this->minimumPurchaseAmount * 100);
        }
        if ($this->onePerCustomer) {
            $rules['one_per_customer'] = true;
        }

        $data = [
            'store_id' => $store->id,
            'type' => $this->type,
            'code' => $this->type === 'code' ? ($this->code ?: null) : null,
            'value_type' => $this->valueType,
            'value_amount' => $valueAmount,
            'starts_at' => $this->startsAt ? \Carbon\Carbon::parse($this->startsAt) : now(),
            'ends_at' => $this->endsAt ? \Carbon\Carbon::parse($this->endsAt) : null,
            'usage_limit' => $this->usageLimit ? (int) $this->usageLimit : null,
            'rules_json' => $rules,
            'status' => $this->isActive ? DiscountStatus::Active : DiscountStatus::Disabled,
        ];

        if ($this->isEditing) {
            $this->discount->update($data);
        } else {
            $data['usage_count'] = 0;
            $discount = Discount::create($data);
            $this->discount = $discount;
        }

        $this->dispatch('toast', type: 'success', message: $this->isEditing ? 'Discount updated.' : 'Discount created.');

        if (! $this->isEditing) {
            $this->redirect(route('admin.discounts.edit', $this->discount), navigate: true);
        }
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.admin.discounts.form');
    }
}
