<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Index extends Component
{
    use InteractsWithStore;

    public int $page = 1;

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    #[Computed]
    public function customer(): \App\Models\Customer
    {
        return auth()->guard('customer')->user();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::where('customer_id', $this->customer->id)
            ->orderByDesc('placed_at')
            ->paginate(10, ['*'], 'page', $this->page);
    }
}
