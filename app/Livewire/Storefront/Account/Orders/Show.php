<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Order;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('storefront.layouts.app')]
class Show extends Component
{
    use InteractsWithStore;

    public string $orderNumber = '';

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    #[Computed]
    public function customer(): \App\Models\Customer
    {
        return auth()->guard('customer')->user();
    }

    #[Computed]
    public function order(): Order
    {
        return Order::where('customer_id', $this->customer->id)
            ->where('order_number', $this->orderNumber)
            ->with(['lines.variant.product.media', 'lines.variant.optionValues.option', 'payments', 'fulfillments', 'refunds'])
            ->firstOrFail();
    }

    #[Computed]
    public function orderLines(): SupportCollection
    {
        return $this->order->lines()->with(['variant.product.media', 'variant.optionValues.option'])->get();
    }

    #[Computed]
    public function currency(): string
    {
        return $this->order->currency ?? $this->store()->default_currency;
    }

    #[Computed]
    public function timeline(): SupportCollection
    {
        $events = collect();

        $events->push([
            'title' => 'Order placed',
            'at' => $this->order->placed_at,
            'done' => true,
        ]);

        foreach ($this->order->payments as $payment) {
            $events->push([
                'title' => match ($payment->status) {
                    'captured' => 'Payment received',
                    'pending' => 'Payment pending',
                    default => 'Payment '.$payment->status,
                },
                'at' => $payment->created_at,
                'done' => $payment->status === 'captured' || $payment->status === 'pending',
            ]);
        }

        foreach ($this->order->fulfillments as $fulfillment) {
            if ($fulfillment->delivered_at) {
                $events->push(['title' => 'Order delivered', 'at' => $fulfillment->delivered_at, 'done' => true]);
            } elseif ($fulfillment->shipped_at) {
                $events->push(['title' => 'Order shipped', 'at' => $fulfillment->shipped_at, 'done' => true]);
            }
        }

        if ($this->order->status === 'cancelled') {
            $events->push(['title' => 'Order cancelled', 'at' => $this->order->updated_at, 'done' => true]);
        }

        foreach ($this->order->refunds as $refund) {
            $events->push(['title' => 'Refund processed', 'at' => $refund->created_at, 'done' => true]);
        }

        return $events
            ->filter(fn (array $event) => $event['at'] !== null)
            ->sortByDesc(fn (array $event) => $event['at'])
            ->values();
    }
}
