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
#[Title('Create Discount')]
class Create extends Component
{
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

        $store = app('current_store');

        $discount = Discount::query()->create([
            'store_id' => $store->id,
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

        $this->dispatch('toast', type: 'success', message: 'Discount created.');

        $this->redirect(route('admin.discounts.edit', $discount), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.discounts.create', [
            'types' => DiscountType::cases(),
            'valueTypes' => DiscountValueType::cases(),
            'statuses' => DiscountStatus::cases(),
        ]);
    }
}
