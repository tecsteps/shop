<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    public ?Discount $discount = null;

    public string $code = '';

    public string $type = 'code';

    public string $valueType = 'percent';

    public int $valueAmount = 0;

    public string $status = 'draft';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public ?int $usageLimit = null;

    public ?int $minimumOrderAmount = null;

    public function mount(?Discount $discount = null): void
    {
        if ($discount?->exists) {
            $this->discount = $discount;
            $this->code = $discount->code ?? '';
            $this->type = $discount->type->value;
            $this->valueType = $discount->value_type->value;
            $this->valueAmount = $discount->value_amount;
            $this->status = $discount->status->value;
            $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i');
            $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
            $this->usageLimit = $discount->usage_limit;
            $this->minimumOrderAmount = $discount->rules_json['minimum_order_amount'] ?? null;
        }
    }

    public function save(): void
    {
        $this->validate([
            'code' => $this->type === 'code' ? ['required', 'string', 'max:255'] : ['nullable'],
            'type' => ['required', 'in:code,automatic'],
            'valueType' => ['required', 'in:percent,fixed,free_shipping'],
            'valueAmount' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,active,expired,disabled'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
        ]);

        $store = app('current_store');

        $rulesJson = [];
        if ($this->minimumOrderAmount) {
            $rulesJson['minimum_order_amount'] = $this->minimumOrderAmount;
        }

        $data = [
            'store_id' => $store->id,
            'code' => $this->type === 'code' ? strtoupper($this->code) : null,
            'type' => $this->type,
            'value_type' => $this->valueType,
            'value_amount' => $this->valueAmount,
            'status' => $this->status,
            'starts_at' => $this->startsAt ?: now(),
            'ends_at' => $this->endsAt ?: null,
            'usage_limit' => $this->usageLimit,
            'rules_json' => $rulesJson ?: '{}',
        ];

        if ($this->discount?->exists) {
            $this->discount->update($data);
            $discount = $this->discount;
        } else {
            $discount = Discount::create($data);
        }

        $this->dispatch('toast', type: 'success', message: $this->isEditing
            ? __('Discount updated.')
            : __('Discount created.')
        );

        $this->redirect(route('admin.discounts.edit', $discount), navigate: true);
    }

    #[Computed]
    public function isEditing(): bool
    {
        return $this->discount?->exists ?? false;
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.form');
    }
}
