<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Livewire\Admin\Concerns\UsesAdminStore;
use App\Models\Discount;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Form extends Component
{
    use UsesAdminStore;

    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $valueType = 'percent';

    public int $valueAmount = 0;

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public ?int $usageLimit = null;

    public string $status = 'active';

    public function mount(?Discount $discount = null): void
    {
        $this->discount = $discount?->exists ? $discount : null;

        if ($this->discount === null) {
            return;
        }

        $this->type = $this->discount->type->value;
        $this->code = $this->discount->code ?? '';
        $this->valueType = $this->discount->value_type->value;
        $this->valueAmount = (int) $this->discount->value_amount;
        $this->startsAt = $this->discount->starts_at?->format('Y-m-d\TH:i');
        $this->endsAt = $this->discount->ends_at?->format('Y-m-d\TH:i');
        $this->usageLimit = $this->discount->usage_limit;
        $this->status = $this->discount->status->value;
    }

    public function save(): mixed
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(array_map(fn (DiscountType $type): string => $type->value, DiscountType::cases()))],
            'code' => ['nullable', 'string', 'max:255'],
            'valueType' => ['required', Rule::in(array_map(fn (DiscountValueType $type): string => $type->value, DiscountValueType::cases()))],
            'valueAmount' => ['required', 'integer', 'min:0'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'usageLimit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in(array_map(fn (DiscountStatus $status): string => $status->value, DiscountStatus::cases()))],
        ]);

        $payload = [
            'store_id' => $this->currentStore()->id,
            'type' => $validated['type'],
            'code' => $validated['type'] === DiscountType::Code->value ? strtoupper($validated['code'] ?? '') : null,
            'value_type' => $validated['valueType'],
            'value_amount' => $validated['valueAmount'],
            'starts_at' => $validated['startsAt'] ?: null,
            'ends_at' => $validated['endsAt'] ?: null,
            'usage_limit' => $validated['usageLimit'],
            'rules_json' => [],
            'status' => $validated['status'],
        ];

        $this->discount = $this->discount === null
            ? Discount::query()->create($payload)
            : tap($this->discount)->update($payload);

        $this->notify('Discount saved.');

        return $this->redirect(route('admin.discounts.edit', $this->discount), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.form', [
            'types' => DiscountType::cases(),
            'valueTypes' => DiscountValueType::cases(),
            'statuses' => DiscountStatus::cases(),
        ])->layout('livewire.admin.layout.app', [
            'title' => $this->discount ? 'Edit discount' : 'Create discount',
        ]);
    }
}
