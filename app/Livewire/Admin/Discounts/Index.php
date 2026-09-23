<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $title = '';

    public string $code = '';

    public string $type = 'percentage';

    public int $value = 10;

    public ?int $minimumSubtotalAmount = null;

    public function save(): void
    {
        Gate::authorize('manage-discounts');
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64'],
            'type' => ['required', 'in:percentage,fixed_amount,free_shipping'],
            'value' => ['required', 'integer', 'min:0', 'max:1000000'],
            'minimumSubtotalAmount' => ['nullable', 'integer', 'min:0'],
        ]);
        Discount::create([
            'store_id' => app('current_store')->id,
            'title' => $this->title,
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'value' => $this->type === 'free_shipping' ? 0 : $this->value,
            'minimum_subtotal_amount' => $this->minimumSubtotalAmount,
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'is_active' => true,
        ]);
        $this->reset(['title', 'code', 'minimumSubtotalAmount']);
        session()->flash('status', 'Discount saved.');
    }

    public function toggle(int $discountId): void
    {
        Gate::authorize('manage-discounts');
        $discount = Discount::query()->findOrFail($discountId);
        $discount->update(['is_active' => ! $discount->is_active]);
    }

    public function delete(int $discountId): void
    {
        Gate::authorize('manage-discounts');
        Discount::query()->findOrFail($discountId)->delete();
    }

    public function render(): mixed
    {
        Gate::authorize('manage-discounts');

        return view('livewire.admin.discounts.index', ['discounts' => Discount::query()->latest()->paginate(15)])->layout('layouts.admin');
    }
}
