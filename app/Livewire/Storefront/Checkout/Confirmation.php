<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\PaymentMethod;
use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Order confirmation page: order number, line items, totals, and next steps.
 *
 * For bank-transfer orders the mock bank details (from config/shop.php) and the
 * order number as the payment reference are shown. The order is resolved within
 * the current store via the global scope. Storefront teammate owns final styling
 * (task #6).
 */
#[Layout('storefront.layouts.app')]
class Confirmation extends Component
{
    public Order $order;

    /**
     * Resolve the order within the current store, 404 otherwise.
     */
    public function mount(int $orderId): void
    {
        $this->order = Order::query()
            ->with('lines', 'payments')
            ->findOrFail($orderId);
    }

    public function render()
    {
        $isBankTransfer = $this->order->payment_method === PaymentMethod::BankTransfer;

        return view('livewire.storefront.checkout.confirmation', [
            'order' => $this->order,
            'lines' => $this->order->lines,
            'isBankTransfer' => $isBankTransfer,
            'bankDetails' => $isBankTransfer ? config('shop.bank_transfer') : null,
        ]);
    }
}
