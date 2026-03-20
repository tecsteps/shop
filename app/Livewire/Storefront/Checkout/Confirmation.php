<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use Livewire\Component;

class Confirmation extends Component
{
    public ?int $orderId = null;

    public string $orderNumber = '';

    public string $email = '';

    public string $paymentMethod = '';

    public string $currency = 'USD';

    public int $subtotalAmount = 0;

    public int $discountAmount = 0;

    public int $shippingAmount = 0;

    public int $taxAmount = 0;

    public int $totalAmount = 0;

    /** @var array<string, mixed> */
    public array $shippingAddress = [];

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];

    public ?int $customerId = null;

    public function mount(int $order): void
    {
        $orderModel = Order::with('lines.variant.product', 'customer')->findOrFail($order);

        $this->orderId = $orderModel->id;
        $this->orderNumber = $orderModel->order_number;
        $this->email = $orderModel->email ?? '';
        $this->paymentMethod = $orderModel->payment_method->value ?? $orderModel->payment_method;
        $this->currency = $orderModel->currency;
        $this->subtotalAmount = $orderModel->subtotal_amount;
        $this->discountAmount = $orderModel->discount_amount;
        $this->shippingAmount = $orderModel->shipping_amount;
        $this->taxAmount = $orderModel->tax_amount;
        $this->totalAmount = $orderModel->total_amount;
        $this->shippingAddress = $orderModel->shipping_address_json ?? [];
        $this->customerId = $orderModel->customer_id;

        $this->lines = $orderModel->lines->map(function ($line) {
            return [
                'title' => $line->title_snapshot,
                'sku' => $line->sku_snapshot,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price_amount,
                'total' => $line->total_amount,
            ];
        })->toArray();
    }

    public function render(): mixed
    {
        return view('livewire.storefront.checkout.confirmation')
            ->layout('layouts.storefront.app', ['title' => 'Order Confirmation']);
    }
}
