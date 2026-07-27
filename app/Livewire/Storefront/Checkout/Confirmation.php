<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\PaymentMethod;
use App\Models\Checkout;
use App\Models\Order;
use App\Support\OrderToken;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Order confirmation page at GET /checkout/{checkoutId}/confirmation
 * (spec 04 §9). Shows the placed order, totals, shipping address and — for
 * bank transfer orders — the mock transfer instructions. The checkout id
 * acts as the access token; the global store scope prevents cross-store
 * leaks.
 */
class Confirmation extends Component
{
    public Checkout $checkout;

    public Order $order;

    /**
     * Load the checkout and its order; 404 when either is missing.
     */
    public function mount(string $checkoutId): void
    {
        $checkout = Checkout::find((int) $checkoutId);

        abort_if($checkout === null, 404);

        $order = Order::with(['lines.variant', 'payments'])
            ->where('checkout_id', $checkout->id)
            ->first();

        abort_if($order === null, 404);

        $this->checkout = $checkout;
        $this->order = $order;
    }

    /**
     * Tokenized URL of the guest order status API endpoint.
     */
    public function orderStatusUrl(): string
    {
        return '/api/storefront/v1/orders/'.urlencode($this->order->order_number).'?token='.OrderToken::for($this->order);
    }

    /**
     * Render the confirmation page.
     */
    public function render(): View
    {
        return view('livewire.storefront.checkout.confirmation', [
            'paymentLast4' => $this->paymentLast4(),
            'isBankTransfer' => $this->order->payment_method === PaymentMethod::BankTransfer,
        ])
            ->layout('storefront.layouts.app')
            ->title('Order '.$this->order->order_number.' - '.app('current_store')->name);
    }

    /**
     * Last four card digits from the sanitized provider payload, if any.
     */
    private function paymentLast4(): ?string
    {
        return $this->order->payments->first()?->raw_json_encrypted['card_last4'] ?? null;
    }
}
