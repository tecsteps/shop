<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Form extends Component
{
    use AuthorizesRequests;

    public ?Discount $discount = null;

    public string $type = 'code';

    public string $code = '';

    public string $value_type = 'percent';

    public int $value_amount = 0;

    public string $starts_at = '';

    public string $ends_at = '';

    public ?int $usage_limit = null;

    public string $status = 'draft';

    public function mount(?Discount $discount = null): void
    {
        if ($discount && $discount->exists) {
            $this->authorize('update', Discount::class);
            $this->discount = $discount;
            $this->type = $discount->type->value;
            $this->code = $discount->code ?? '';
            $this->value_type = $discount->value_type->value;
            $this->value_amount = $discount->value_amount;
            $this->starts_at = $discount->starts_at?->format('Y-m-d\TH:i') ?? '';
            $this->ends_at = $discount->ends_at?->format('Y-m-d\TH:i') ?? '';
            $this->usage_limit = $discount->usage_limit;
            $this->status = $discount->status->value;
        } else {
            $this->authorize('create', Discount::class);
        }
    }

    public function save(): void
    {
        $this->validate([
            'type' => ['required', 'in:code,automatic'],
            'code' => ['required', 'string', 'max:255'],
            'value_type' => ['required', 'in:fixed,percent,free_shipping'],
            'value_amount' => ['required', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,active,expired,disabled'],
        ]);

        $store = app('current_store');

        $data = [
            'store_id' => $store->id,
            'type' => $this->type,
            'code' => $this->code,
            'value_type' => $this->value_type,
            'value_amount' => $this->value_amount,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
            'usage_limit' => $this->usage_limit,
            'status' => $this->status,
        ];

        if ($this->discount) {
            $this->discount->update($data);
            session()->flash('toast', ['type' => 'success', 'message' => __('Discount updated.')]);
        } else {
            Discount::query()->create($data + ['usage_count' => 0, 'rules_json' => []]);
            session()->flash('toast', ['type' => 'success', 'message' => __('Discount created.')]);
        }

        $this->redirect(route('admin.discounts.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.form');
    }
}
