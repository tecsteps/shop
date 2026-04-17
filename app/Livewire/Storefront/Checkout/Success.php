<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class Success extends Component
{
    public ?string $order = null;

    public function mount(): void
    {
        $this->order = request()->query('order');
    }

    public function render(): View
    {
        $order = null;

        if ($this->order !== null) {
            $query = Order::query();

            if (app()->bound('current_store')) {
                $query->where('store_id', app('current_store')->getKey());
            }

            $order = $query->where('order_number', $this->order)->first();
        }

        return view('livewire.storefront.checkout.success', [
            'order' => $order,
        ]);
    }
}
