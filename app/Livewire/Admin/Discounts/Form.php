<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Form extends Component
{
    public ?Discount $discount = null;

    #[Validate('required|string|max:50')]
    public string $code = '';

    public string $type = 'code';

    public string $valueType = 'percent';

    public int $valueAmount = 10;

    public ?string $startsAt = null;

    public ?string $endsAt = null;

    public ?int $usageLimit = null;

    public string $status = 'active';

    public function mount(?Discount $discount = null): void
    {
        if ($discount?->exists) {
            $this->discount = $discount;
            $this->code = (string) $discount->code;
            $this->type = $discount->type->value;
            $this->valueType = $discount->value_type->value;
            $this->valueAmount = $discount->value_amount;
            $this->startsAt = $discount->starts_at?->format('Y-m-d');
            $this->endsAt = $discount->ends_at?->format('Y-m-d');
            $this->usageLimit = $discount->usage_limit;
            $this->status = $discount->status->value;
        }
    }

    public function save(): mixed
    {
        $this->validate();

        $data = [
            'store_id' => app('current_store')->id,
            'type' => $this->type,
            'code' => strtoupper($this->code),
            'value_type' => $this->valueType,
            'value_amount' => $this->valueAmount,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'usage_limit' => $this->usageLimit,
            'status' => $this->status,
            'rules_json' => [],
        ];

        if ($this->discount) {
            $this->discount->update($data);
        } else {
            $this->discount = Discount::create($data);
        }

        session()->flash('success', 'Discount saved.');

        return $this->redirect(route('admin.discounts.edit', $this->discount), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.discounts.form')
            ->title($this->discount ? 'Edit discount' : 'New discount');
    }
}
