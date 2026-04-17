<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Component;

class Form extends Component
{
    public ?int $discountId = null;

    public string $code = '';

    public string $type = 'code';

    public string $value_type = 'percent';

    public string $value_amount = '';

    public string $status = 'draft';

    public string $usage_limit = '';

    public string $starts_at = '';

    public string $ends_at = '';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:code,automatic'],
            'value_type' => ['required', 'in:percent,fixed,free_shipping'],
            'value_amount' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,active,expired,disabled'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    public function mount(?int $discountId = null): void
    {
        if ($discountId) {
            $discount = Discount::findOrFail($discountId);
            $this->discountId = $discount->id;
            $this->code = $discount->code;
            $this->type = $discount->type->value;
            $this->value_type = $discount->value_type->value;
            $this->value_amount = (string) $discount->value_amount;
            $this->status = $discount->status->value;
            $this->usage_limit = $discount->usage_limit !== null ? (string) $discount->usage_limit : '';
            $this->starts_at = $discount->starts_at?->format('Y-m-d\TH:i') ?? '';
            $this->ends_at = $discount->ends_at?->format('Y-m-d\TH:i') ?? '';
        }
    }

    public function save(): mixed
    {
        $this->validate();

        $store = app('current_store');

        $data = [
            'code' => $this->code,
            'type' => $this->type,
            'value_type' => $this->value_type,
            'value_amount' => (int) $this->value_amount,
            'status' => $this->status,
            'usage_limit' => $this->usage_limit !== '' ? (int) $this->usage_limit : null,
            'starts_at' => $this->starts_at ?: now(),
            'ends_at' => $this->ends_at ?: null,
        ];

        if ($this->discountId) {
            $discount = Discount::findOrFail($this->discountId);
            $discount->update($data);
            $this->dispatch('toast', type: 'success', message: 'Discount updated.');
        } else {
            $data['store_id'] = $store->id;
            $data['usage_count'] = 0;
            $discount = Discount::create($data);
            session()->flash('toast', ['type' => 'success', 'message' => 'Discount created.']);

            return redirect()->route('admin.discounts.edit', $discount);
        }

        return null;
    }

    public function render(): mixed
    {
        $isEdit = (bool) $this->discountId;

        return view('livewire.admin.discounts.form', [
            'isEdit' => $isEdit,
        ])->layout('layouts.admin.app', [
            'title' => $isEdit ? "Edit {$this->code}" : 'New Discount',
        ]);
    }
}
