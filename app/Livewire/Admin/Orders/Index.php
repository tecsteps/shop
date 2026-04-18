<?php

namespace App\Livewire\Admin\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    public function updating($name, $value): void
    {
        if (in_array($name, ['search', 'status', 'startDate', 'endDate'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $query = Order::query()
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('order_number', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->startDate !== '', fn ($q) => $q->whereDate('placed_at', '>=', $this->startDate))
            ->when($this->endDate !== '', fn ($q) => $q->whereDate('placed_at', '<=', $this->endDate))
            ->orderByDesc('placed_at');

        return view('livewire.admin.orders.index', [
            'orders' => $query->paginate(20),
            'statuses' => OrderStatus::cases(),
        ]);
    }
}
