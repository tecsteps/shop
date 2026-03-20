<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Form extends Component
{
    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public int $valueAmount = 0;

    public string $status = 'active';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public ?int $usageLimit = null;

    public ?int $minimumPurchaseAmount = null;

    public function mount(?Discount $discount = null): void
    {
        if ($discount && $discount->exists) {
            $this->discount = $discount;
            $this->type = $discount->type->value;
            $this->code = $discount->code ?? '';
            $this->valueType = $discount->value_type->value;
            $this->valueAmount = $discount->value_amount;
            $this->status = $discount->status->value;
            $this->startsAt = $discount->starts_at;
            $this->endsAt = $discount->ends_at;
            $this->usageLimit = $discount->usage_limit;
            $this->minimumPurchaseAmount = $discount->minimum_purchase_amount;
        }
    }

    public function generateCode(): void
    {
        $this->code = strtoupper(Str::random(8));
    }

    public function save(): void
    {
        $storeId = app('current_store')->id;

        $rules = [
            'type' => ['required', Rule::in(['code', 'automatic'])],
            'valueType' => ['required', Rule::in(['percent', 'fixed', 'free_shipping'])],
            'valueAmount' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'disabled', 'expired', 'draft'])],
        ];

        if ($this->type === 'code') {
            $rules['code'] = [
                'required', 'string', 'max:255',
                Rule::unique('discounts', 'code')
                    ->where('store_id', $storeId)
                    ->ignore($this->discount?->id),
            ];
        }

        $this->validate($rules);

        $data = [
            'store_id' => $storeId,
            'type' => $this->type,
            'code' => $this->type === 'code' ? $this->code : null,
            'value_type' => $this->valueType,
            'value_amount' => $this->valueAmount,
            'status' => $this->status,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'usage_limit' => $this->usageLimit,
            'minimum_purchase_amount' => $this->minimumPurchaseAmount,
        ];

        if ($this->discount && $this->discount->exists) {
            $this->discount->update($data);
        } else {
            $this->discount = Discount::withoutGlobalScopes()->create($data);
        }

        $this->dispatch('toast', type: 'success', message: 'Discount saved.');
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->discount !== null && $this->discount->exists;
    }

    public function render(): mixed
    {
        $breadcrumbs = [
            ['label' => 'Discounts', 'url' => route('admin.discounts.index')],
            ['label' => $this->isEditing ? ($this->discount->code ?? 'Automatic') : 'Create discount'],
        ];

        return view('livewire.admin.discounts.form')
            ->layout('layouts.admin', ['breadcrumbs' => $breadcrumbs]);
    }
}
