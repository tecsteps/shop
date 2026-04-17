<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    public ?Discount $discount = null;

    #[Validate('required|string|max:50')]
    public string $code = '';

    #[Validate('required|string')]
    public string $type = 'code';

    #[Validate('required|string')]
    public string $valueType = 'percent';

    #[Validate('required|integer|min:0')]
    public int $value = 0;

    #[Validate('nullable|date')]
    public ?string $startsAt = null;

    #[Validate('nullable|date|after:startsAt')]
    public ?string $endsAt = null;

    #[Validate('nullable|integer|min:0')]
    public ?int $usageLimit = null;

    #[Validate('integer|min:0')]
    public int $minimumPurchaseAmount = 0;

    #[Validate('required|string')]
    public string $status = 'draft';

    public function mount(?Discount $discount = null): void
    {
        if ($discount?->exists) {
            $this->discount = $discount;
            $this->code = (string) $discount->code;
            $this->type = $discount->type->value;
            $this->valueType = $discount->value_type->value;
            $this->value = $discount->value_amount;
            $this->startsAt = $discount->starts_at?->format('Y-m-d');
            $this->endsAt = $discount->ends_at?->format('Y-m-d');
            $this->usageLimit = $discount->usage_limit;
            $this->minimumPurchaseAmount = $discount->minimum_purchase_amount ?? 0;
            $this->status = $discount->status->value;
        }
    }

    public function save(): void
    {
        $this->validate();

        /** @var Store $store */
        $store = app('current_store');

        $data = [
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'value_type' => $this->valueType,
            'value_amount' => $this->value,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'usage_limit' => $this->usageLimit,
            'minimum_purchase_amount' => $this->minimumPurchaseAmount,
            'status' => $this->status,
        ];

        if ($this->discount) {
            $this->discount->update($data);
            $this->dispatch('toast', type: 'success', message: 'Discount updated successfully.');
        } else {
            $discount = Discount::query()->withoutGlobalScopes()->create(array_merge($data, [
                'store_id' => $store->id,
                'usage_count' => 0,
            ]));

            $this->redirect(route('admin.discounts.edit', $discount), navigate: true);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.form');
    }
}
