<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $status = 'all';

    public string $search = '';

    public function render(): mixed
    {
        Gate::authorize('viewAny', Order::class);
        $orders = Order::query()->with('customer')->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->search !== '', fn ($query) => $query->where('order_number', 'like', '%'.addcslashes($this->search, '%_\\').'%'))
            ->latest('placed_at');

        return view('livewire.admin.orders.index', ['orders' => $orders->paginate(15)])->layout('layouts.admin');
    }
}
