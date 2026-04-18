<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $value_type = 'percent';

    public int $value_amount = 0;

    public string $starts_at = '';

    public string $ends_at = '';

    public ?int $usage_limit = null;

    public int $minimum_purchase_amount = 0;

    public string $status = 'active';

    public function mount(?Discount $discount = null): void
    {
        if ($discount && $discount->exists) {
            $this->discount = $discount;
            $this->type = $discount->type?->value ?? 'code';
            $this->code = (string) $discount->code;
            $this->value_type = $discount->value_type?->value ?? 'percent';
            $this->value_amount = (int) $discount->value_amount;
            $this->starts_at = $discount->starts_at?->format('Y-m-d\TH:i') ?? '';
            $this->ends_at = $discount->ends_at?->format('Y-m-d\TH:i') ?? '';
            $this->usage_limit = $discount->usage_limit;
            $this->minimum_purchase_amount = (int) ($discount->rules_json['minimum_purchase_amount'] ?? 0);
            $this->status = $discount->status?->value ?? 'active';
        } else {
            $this->starts_at = now()->format('Y-m-d\TH:i');
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'type' => 'required|in:code,automatic',
            'code' => 'nullable|string|max:100',
            'value_type' => 'required|in:percent,fixed,free_shipping',
            'value_amount' => 'required|integer|min:0',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_purchase_amount' => 'nullable|integer|min:0',
            'status' => 'required|in:draft,active,expired,disabled',
        ]);

        $store = app('current_store');

        $payload = [
            'store_id' => $store->id,
            'type' => $data['type'],
            'code' => $data['code'] ?: null,
            'value_type' => $data['value_type'],
            'value_amount' => (int) $data['value_amount'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?: null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'rules_json' => ['minimum_purchase_amount' => (int) $data['minimum_purchase_amount']],
            'status' => $data['status'],
        ];

        if ($this->discount && $this->discount->exists) {
            $this->discount->update($payload);
            $discount = $this->discount;
        } else {
            $discount = Discount::create($payload);
            $this->discount = $discount;
        }

        session()->flash('success', 'Discount saved.');

        $this->redirect(route('admin.discounts.edit', $discount), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.discounts.form', [
            'statuses' => DiscountStatus::cases(),
            'types' => DiscountType::cases(),
            'valueTypes' => DiscountValueType::cases(),
        ]);
    }
}
