<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Index extends Component
{
    use WithPagination;

    #[Locked]
    public int $storeId;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $financialStatusFilter = 'all';

    public string $fulfillmentStatusFilter = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        $this->storeId = $store->getKey();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFinancialStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFulfillmentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function orders(): LengthAwarePaginator
    {
        $dateFrom = $this->parsedDate($this->dateFrom);
        $dateTo = $this->parsedDate($this->dateTo, endOfDay: true);

        return Order::withoutGlobalScopes()
            ->with('customer')
            ->withCount('lines')
            ->where('store_id', $this->storeId)
            ->when($this->search !== '', function (Builder $query): void {
                $search = '%'.$this->search.'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('order_number', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhereHas('customer', fn (Builder $query) => $query->where('name', 'like', $search));
                });
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->financialStatusFilter !== 'all', fn (Builder $query) => $query->where('financial_status', $this->financialStatusFilter))
            ->when($this->fulfillmentStatusFilter !== 'all', fn (Builder $query) => $query->where('fulfillment_status', $this->fulfillmentStatusFilter))
            ->when($dateFrom instanceof Carbon, fn (Builder $query) => $query->where('placed_at', '>=', $dateFrom))
            ->when($dateTo instanceof Carbon, fn (Builder $query) => $query->where('placed_at', '<=', $dateTo))
            ->latest('placed_at')
            ->latest('id')
            ->paginate(20);
    }

    public function render(): mixed
    {
        return view('livewire.admin.orders.index', [
            'orders' => $this->orders(),
            'statuses' => OrderStatus::cases(),
            'financialStatuses' => FinancialStatus::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
        ])->layout('layouts.app', [
            'title' => __('Orders'),
        ]);
    }

    private function parsedDate(string $value, bool $endOfDay = false): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::parse($value);

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
