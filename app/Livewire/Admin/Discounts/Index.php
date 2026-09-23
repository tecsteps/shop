<?php

namespace App\Livewire\Admin\Discounts;

use App\Models\Discount;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        if (! in_array($this->statusFilter, ['all', 'active', 'expired', 'scheduled'], true)) {
            $this->statusFilter = 'all';
        }

        $this->resetPage();
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
        Gate::authorize('view-discounts');
        $now = now();
        $search = trim($this->search);
        $discounts = Discount::query()
            ->when($search !== '', fn ($query) => $query->where('code', 'like', "%{$search}%"))
            ->when($this->statusFilter === 'active', fn ($query) => $query
                ->where('is_active', true)
                ->where('starts_at', '<=', $now)
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhere('ends_at', '>=', $now)))
            ->when($this->statusFilter === 'scheduled', fn ($query) => $query->where('is_active', true)->where('starts_at', '>', $now))
            ->when($this->statusFilter === 'expired', fn ($query) => $query->whereNotNull('ends_at')->where('ends_at', '<', $now))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.discounts.index', [
            'canManage' => Gate::allows('manage-discounts'),
            'discounts' => $discounts,
        ])->layout('layouts.admin');
    }
}
