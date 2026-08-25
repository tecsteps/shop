<?php

namespace App\Livewire\Admin\Discounts;

use App\Livewire\Admin\Concerns\DispatchesToasts;
use App\Livewire\Admin\Concerns\FormatsMoney;
use App\Models\Discount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use DispatchesToasts, FormatsMoney, WithPagination;

    #[Layout('layouts.admin.app')]
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

    #[Computed]
    public function discounts(): LengthAwarePaginator
    {
        $query = Discount::query();

        if (trim($this->search) !== '') {
            $query->where('code', 'like', '%'.trim($this->search).'%');
        }

        match ($this->statusFilter) {
            'active' => $query->where('status', 'active')
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now())),
            'scheduled' => $query->where('status', 'active')->where('starts_at', '>', now()),
            'expired' => $query->where(function ($q) {
                $q->where('status', 'expired')
                    ->orWhere(fn ($q2) => $q2->where('status', 'active')->where('ends_at', '<', now()));
            }),
            default => null,
        };

        return $query->latest()->paginate(15);
    }

    /**
     * Resolve the effective display status of a discount.
     */
    public function displayStatus(Discount $discount): string
    {
        if ($discount->status !== 'active') {
            return $discount->status;
        }

        if ($discount->starts_at?->isFuture()) {
            return 'scheduled';
        }

        if ($discount->ends_at?->isPast()) {
            return 'expired';
        }

        return 'active';
    }

    public function render()
    {
        return view('livewire.admin.discounts.index');
    }
}
