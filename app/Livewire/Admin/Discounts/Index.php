<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function mount(): void
    {
        $this->authorize('viewAny', Discount::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function delete(int $discountId): void
    {
        $discount = Discount::query()->findOrFail($discountId);
        $this->authorize('delete', $discount);
        $discount->delete();
        $this->dispatch('toast', message: 'Discount deleted.');
    }

    public function render(): mixed
    {
        $search = trim($this->search);
        $now = Carbon::now();
        $discounts = Discount::query()
            ->when($search !== '', fn ($query) => $query->where('code', 'like', '%'.$search.'%'))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('status', 'active')->where(function ($query) use ($now): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            }))
            ->when($this->statusFilter === 'scheduled', fn ($query) => $query->where('starts_at', '>', $now))
            ->when($this->statusFilter === 'expired', fn ($query) => $query->where(function ($query) use ($now): void {
                $query->where('ends_at', '<=', $now)
                    ->orWhere(function ($query): void {
                        $query->whereNotNull('usage_limit')->whereColumn('usage_count', '>=', 'usage_limit');
                    });
            }))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.discounts.index', compact('discounts'))->layout('layouts.admin');
    }
}
