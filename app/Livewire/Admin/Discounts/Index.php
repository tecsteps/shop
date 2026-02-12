<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Models\Discount;
use App\Support\MoneyFormatter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
#[Title('Discounts')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $store = app('current_store');

        Discount::query()
            ->where('store_id', $store->id)
            ->where('id', $id)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'Discount deleted.');
    }

    public function formatDiscount(Discount $discount): string
    {
        return match ($discount->value_type) {
            \App\Enums\DiscountValueType::Percent => $discount->value_amount.'%',
            \App\Enums\DiscountValueType::Fixed => MoneyFormatter::format($discount->value_amount),
            \App\Enums\DiscountValueType::FreeShipping => 'Free shipping',
        };
    }

    public function render()
    {
        $store = app('current_store');

        $discounts = Discount::query()
            ->where('store_id', $store->id)
            ->when($this->search, fn ($q) => $q->where('code', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.discounts.index', [
            'discounts' => $discounts,
            'statuses' => DiscountStatus::cases(),
        ]);
    }
}
