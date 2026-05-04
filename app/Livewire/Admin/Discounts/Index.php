<?php

namespace App\Livewire\Admin\Discounts;

use App\Enums\DiscountStatus;
use App\Enums\DiscountValueType;
use App\Models\Discount;
use App\Models\Store;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Locked]
    public int $storeId;

    public string $storeCurrency = 'EUR';

    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->authorize('viewAny', Discount::class);

        $this->storeId = $store->getKey();
        $this->storeCurrency = $store->default_currency;
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

    public function discounts(): LengthAwarePaginator
    {
        return Discount::withoutGlobalScopes()
            ->where('store_id', $this->storeId)
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('code', 'like', $search)
                        ->orWhere('type', 'like', $search);
                });
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $this->applyStatusFilter($query))
            ->when($this->typeFilter !== 'all', fn (Builder $query) => $query->where('type', $this->typeFilter))
            ->latest('starts_at')
            ->latest('id')
            ->paginate(20);
    }

    public function valueLabel(Discount $discount): string
    {
        return match ($discount->value_type) {
            DiscountValueType::Percent => $discount->value_amount.'%',
            DiscountValueType::Fixed => Money::format($discount->value_amount, $this->storeCurrency),
            DiscountValueType::FreeShipping => 'Free shipping',
        };
    }

    public function effectiveStatus(Discount $discount): string
    {
        if ($discount->status === DiscountStatus::Disabled) {
            return 'disabled';
        }

        if ($discount->starts_at->isFuture()) {
            return 'scheduled';
        }

        if ($discount->status === DiscountStatus::Expired || ($discount->ends_at !== null && $discount->ends_at->isPast())) {
            return 'expired';
        }

        return $discount->status->value;
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'active' => 'green',
            'expired' => 'red',
            'scheduled' => 'amber',
            default => 'zinc',
        };
    }

    public function render(): mixed
    {
        return view('livewire.admin.discounts.index', [
            'discounts' => $this->discounts(),
        ])->layout('layouts.app', [
            'title' => __('Discounts'),
        ]);
    }

    private function applyStatusFilter(Builder $query): void
    {
        match ($this->statusFilter) {
            'active' => $query
                ->where('status', DiscountStatus::Active->value)
                ->where('starts_at', '<=', now())
                ->where(function (Builder $query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                }),
            'expired' => $query->where(function (Builder $query): void {
                $query
                    ->where('status', DiscountStatus::Expired->value)
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->where('status', DiscountStatus::Active->value)
                            ->where('ends_at', '<', now());
                    });
            }),
            'scheduled' => $query
                ->where('status', DiscountStatus::Active->value)
                ->where('starts_at', '>', now()),
            default => null,
        };
    }
}
