<?php

namespace App\Livewire\Storefront\Checkout;

use App\Enums\CheckoutStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Storefront\Concerns\ResolvesStore;
use App\Models\Checkout;
use App\Services\OrderService;
use App\Services\StorefrontCatalog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Confirmation extends Component
{
    use ResolvesStore;

    public int $checkoutId;

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;

        $checkout = Checkout::query()
            ->where('store_id', $this->store()->getKey())
            ->whereKey($this->checkoutId)
            ->first();

        if ($checkout !== null && $checkout->status !== CheckoutStatus::Completed) {
            $this->redirect(route('storefront.checkout.show', ['checkoutId' => $checkout->getKey()]), navigate: true);
        }
    }

    public function render()
    {
        $store = $this->store();

        $checkout = Checkout::query()
            ->where('store_id', $store->getKey())
            ->whereKey($this->checkoutId)
            ->first();

        abort_if($checkout === null, 404);
        abort_if($checkout->status !== CheckoutStatus::Completed, 404);

        $order = app(OrderService::class)->findByCheckout($checkout);

        abort_if($order === null, 404);

        $order->load(['orderLines', 'payments']);
        $catalog = app(StorefrontCatalog::class);
        $customer = Auth::guard('customer')->user();

        $payment = $order->payments->first();
        $snapshot = [];

        if ($payment !== null && is_string($payment->raw_json_encrypted)) {
            $snapshot = json_decode($payment->raw_json_encrypted, true) ?? [];
        }

        return view('livewire.storefront.checkout.confirmation', [
            'store' => $store,
            'checkout' => $checkout,
            'order' => $order,
            'paymentLabel' => $this->paymentLabel($order->payment_method, $snapshot),
            'bankInstructions' => $order->payment_method === PaymentMethod::BankTransfer ? [
                'bank_name' => 'Mock Bank AG',
                'iban' => 'DE89 3704 0044 0532 0130 00',
                'bic' => 'COBADEFFXXX',
                'reference' => $order->order_number,
                'amount_formatted' => \App\Support\Money::format($order->total_amount, $order->currency),
            ] : null,
            'ownsOrder' => $customer !== null && $order->customer_id !== null && (int) $order->customer_id === (int) $customer->getKey(),
        ])->layout('storefront.layouts.app', [
            'title' => 'Thank you for your order - '.$store->name,
            'metaDescription' => 'Order confirmation at '.$store->name,
        ]);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    protected function paymentLabel(?PaymentMethod $method, array $snapshot): string
    {
        if ($method === PaymentMethod::CreditCard) {
            $last4 = $snapshot['card_last4'] ?? null;

            return $last4 ? 'Credit Card ending in '.$last4 : 'Credit Card';
        }

        if ($method === PaymentMethod::Paypal) {
            return 'PayPal';
        }

        if ($method === PaymentMethod::BankTransfer) {
            return 'Bank Transfer';
        }

        return 'Unknown';
    }
}
