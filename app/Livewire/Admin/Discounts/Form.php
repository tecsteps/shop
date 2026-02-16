<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.admin.layout', ['title' => 'Discount'])]
class Form extends Component
{
    public ?Discount $discount = null;

    public string $code = '';

    public string $title = '';

    public string $type = 'code';

    public string $value_type = 'percent';

    public string $value_amount = '';

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public string $usage_limit = '';

    public string $minimum_purchase = '';

    public string $status = 'draft';

    public function mount(?Discount $discount = null): void
    {
        if ($discount && $discount->exists) {
            $this->authorize('update', $discount);
            $this->discount = $discount;
            $this->code = $discount->code ?? '';
            $this->title = $discount->title ?? '';
            $this->type = $discount->type->value;
            $this->value_type = $discount->value_type->value;
            $this->value_amount = $discount->value_amount ? (string) ($discount->value_amount / 100) : '';
            $this->starts_at = $discount->starts_at?->format('Y-m-d\TH:i');
            $this->ends_at = $discount->ends_at?->format('Y-m-d\TH:i');
            $this->usage_limit = $discount->usage_limit ? (string) $discount->usage_limit : '';
            $this->minimum_purchase = $discount->minimum_purchase ? (string) ($discount->minimum_purchase / 100) : '';
            $this->status = $discount->status->value;
        }
    }

    public function save(): void
    {
        if ($this->discount) {
            $this->authorize('update', $this->discount);
        } else {
            $this->authorize('create', Discount::class);
        }

        $this->validate([
            'code' => ['required', 'string', 'max:50'],
            'value_type' => ['required', 'in:percent,fixed,free_shipping'],
            'status' => ['required', 'in:draft,active,expired,disabled'],
        ]);

        $store = app('current_store');

        $data = [
            'store_id' => $store->id,
            'code' => $this->code,
            'title' => $this->title ?: $this->code,
            'type' => $this->type,
            'value_type' => $this->value_type,
            'value_amount' => $this->value_amount ? (int) (((float) $this->value_amount) * 100) : 0,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
            'usage_limit' => $this->usage_limit ? (int) $this->usage_limit : null,
            'minimum_purchase' => $this->minimum_purchase ? (int) (((float) $this->minimum_purchase) * 100) : null,
            'status' => $this->status,
        ];

        if ($this->discount) {
            $this->discount->update($data);
        } else {
            Discount::query()->create($data);
        }

        session()->flash('success', $this->discount ? 'Discount updated.' : 'Discount created.');
        $this->redirect(route('admin.discounts.index'));
    }

    public function render(): mixed
    {
        return view('livewire.admin.discounts.form');
    }
}
