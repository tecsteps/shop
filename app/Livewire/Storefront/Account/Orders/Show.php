<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Customer-facing order detail: items, addresses, payment, totals, fulfillment.
 *
 * The order is resolved by order number AND scoped to the authenticated
 * customer, so a customer can never view another customer's order (404).
 */
#[Layout('storefront.layouts.app')]
class Show extends Component
{
    public Order $order;

    /**
     * Resolve the order by its number for the authenticated customer.
     *
     * Order numbers carry a non-URL-safe prefix (e.g. "#1001"), so the route
     * segment is the bare digits ("1001"); we match on either the exact stored
     * value or its numeric portion. Scoping to the customer means another
     * customer's order 404s.
     */
    public function mount(string $orderNumber): void
    {
        $digits = preg_replace('/\D/', '', $orderNumber);

        $this->order = Order::query()
            ->where('customer_id', Auth::guard('customer')->id())
            ->where(function ($query) use ($orderNumber, $digits): void {
                $query->where('order_number', $orderNumber)
                    ->orWhereRaw("REPLACE(REPLACE(order_number, '#', ''), ' ', '') = ?", [$digits]);
            })
            ->with(['lines', 'fulfillments', 'payments'])
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.storefront.account.orders.show', [
            'order' => $this->order,
            'lines' => $this->order->lines,
            'fulfillments' => $this->order->fulfillments,
        ]);
    }
}
