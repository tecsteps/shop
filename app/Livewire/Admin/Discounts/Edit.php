<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Edit Discount')]
class Edit extends Component
{
    public Discount $discount;

    public string $code = '';

    public string $title = '';

    public string $type = 'code';

    public string $valueType = 'percent';

    public int $valueAmount = 0;

    public ?int $minimumPurchaseAmount = null;

    public ?int $usageLimit = null;

    public string $status = 'draft';

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public function mount(Discount $discount): void
    {
        $store = app('current_store');
        abort_unless($discount->store_id === $store->id, 404);

        $this->discount = $discount;
        $this->code = $discount->code;
        $this->title = $discount->title ?? '';
        $this->type = $discount->type->value;
        $this->valueType = $discount->value_type->value;
        $this->valueAmount = $discount->value_amount;
        $this->minimumPurchaseAmount = $discount->minimum_purchase_amount;
        $this->usageLimit = $discount->usage_limit;
        $this->status = $discount->status->value;
        $this->startsAt = $discount->starts_at?->format('Y-m-d\TH:i');
        $this->endsAt = $discount->ends_at?->format('Y-m-d\TH:i');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'code' => ['required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:code,automatic'],
            'valueType' => ['required', 'in:percent,fixed,free_shipping'],
            'valueAmount' => ['required', 'integer', 'min:0'],
            'minimumPurchaseAmount' => ['nullable', 'integer', 'min:0'],
            'usageLimit' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,active,expired,disabled'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
        ]);

        $this->discount->update([
            'code' => strtoupper($validated['code']),
            'title' => $validated['title'],
            'type' => $validated['type'],
            'value_type' => $validated['valueType'],
            'value_amount' => $validated['valueAmount'],
            'minimum_purchase_amount' => $validated['minimumPurchaseAmount'],
            'usage_limit' => $validated['usageLimit'],
            'status' => $validated['status'],
            'starts_at' => $validated['startsAt'],
            'ends_at' => $validated['endsAt'],
        ]);

        $this->dispatch('toast', type: 'success', message: 'Discount updated.');
    }

    public function render()
    {
        return view('livewire.admin.discounts.edit', [
            'types' => DiscountType::cases(),
            'valueTypes' => DiscountValueType::cases(),
            'statuses' => DiscountStatus::cases(),
        ]);
    }
}
