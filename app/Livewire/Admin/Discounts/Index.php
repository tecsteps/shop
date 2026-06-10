<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Discount;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
class Index extends Component
{
    use AuthorizesRequests, SendsToasts, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = 'all';

    #[Url]
    public string $typeFilter = 'all';

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

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Discount>
     */
    #[Computed]
    public function discounts(): LengthAwarePaginator
    {
        return Discount::query()
            ->when($this->search !== '', fn ($query) => $query->where('code', 'like', '%'.$this->search.'%'))
            ->when($this->typeFilter !== 'all', fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->statusFilter !== 'all', function ($query): void {
                match ($this->statusFilter) {
                    'scheduled' => $query
                        ->where('status', DiscountStatus::Active)
                        ->where('starts_at', '>', now()),
                    'expired' => $query->where(function ($query): void {
                        $query->where('status', DiscountStatus::Expired)
                            ->orWhere(fn ($query) => $query->whereNotNull('ends_at')->where('ends_at', '<', now()));
                    }),
                    'active' => $query
                        ->where('status', DiscountStatus::Active)
                        ->where('starts_at', '<=', now())
                        ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now())),
                    default => $query->where('status', $this->statusFilter),
                };
            })
            ->orderByDesc('updated_at')
            ->paginate(15);
    }

    #[Computed]
    public function hasAnyDiscounts(): bool
    {
        return Discount::query()->exists();
    }

    /**
     * The effective display status of a discount: schedule-aware variant of
     * the stored status (spec 03 section 10.1 badge colors).
     */
    public function displayStatus(Discount $discount): string
    {
        if ($discount->status === DiscountStatus::Active && $discount->starts_at !== null && $discount->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($discount->status === DiscountStatus::Active && $discount->ends_at !== null && $discount->ends_at->isPast()) {
            return 'expired';
        }

        return $discount->status->value;
    }

    public function render(): View
    {
        return view('livewire.admin.discounts.index')->title(__('Discounts'));
    }
}
