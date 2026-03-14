<?php

namespace Database\Seeders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::where('handle', 'acme-fashion')->firstOrFail();
        $customer = Customer::where('store_id', $store->id)
            ->where('email', 'customer@acme.test')
            ->firstOrFail();

        $products = Product::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->with('variants')
            ->get();

        $tshirt = $products->firstWhere('handle', 'classic-cotton-t-shirt');
        $jeans = $products->firstWhere('handle', 'premium-slim-fit-jeans');
        $hoodie = $products->firstWhere('handle', 'heavyweight-hoodie');
        $polo = $products->firstWhere('handle', 'polo-shirt');

        $address = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address1' => 'Alexanderplatz 1',
            'city' => 'Berlin',
            'postal_code' => '10178',
            'country_code' => 'DE',
        ];

        // Order #1001: Paid, unfulfilled, credit card
        $this->createOrder1001($store, $customer, $address, $tshirt, $jeans);

        // Order #1002: Paid, fulfilled
        $this->createOrder1002($store, $customer, $address, $hoodie);

        // Order #1004: Pending, bank transfer
        $this->createOrder1004($store, $customer, $address, $polo);

        // Order #1005: Paid, unfulfilled (for admin fulfillment testing)
        $this->createOrder1005($store, $customer, $address, $tshirt, $hoodie);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1001(Store $store, Customer $customer, array $address, Product $tshirt, Product $jeans): void
    {
        $tshirtVariant = $tshirt->variants->first();
        $jeansVariant = $jeans->variants->first();

        $line1Subtotal = $tshirtVariant->price_amount * 2;
        $line2Subtotal = $jeansVariant->price_amount * 1;
        $subtotal = $line1Subtotal + $line2Subtotal;
        $shipping = 499;
        $tax = (int) round($subtotal * 0.19);
        $total = $subtotal + $shipping + $tax;

        $order = Order::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => '1001',
            'email' => $customer->email,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => PaymentMethod::CreditCard,
            'currency' => 'EUR',
            'subtotal_amount' => $subtotal,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'shipping_address_json' => $address,
            'billing_address_json' => $address,
            'placed_at' => now()->subDays(3),
        ]);

        OrderLine::create([
            'order_id' => $order->id,
            'product_id' => $tshirt->id,
            'variant_id' => $tshirtVariant->id,
            'title_snapshot' => $tshirt->title,
            'variant_title_snapshot' => $tshirtVariant->title,
            'sku_snapshot' => $tshirtVariant->sku,
            'quantity' => 2,
            'unit_price_amount' => $tshirtVariant->price_amount,
            'subtotal_amount' => $line1Subtotal,
            'total_amount' => $line1Subtotal,
        ]);

        OrderLine::create([
            'order_id' => $order->id,
            'product_id' => $jeans->id,
            'variant_id' => $jeansVariant->id,
            'title_snapshot' => $jeans->title,
            'variant_title_snapshot' => $jeansVariant->title,
            'sku_snapshot' => $jeansVariant->sku,
            'quantity' => 1,
            'unit_price_amount' => $jeansVariant->price_amount,
            'subtotal_amount' => $line2Subtotal,
            'total_amount' => $line2Subtotal,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider' => 'mock',
            'provider_payment_id' => 'mock_pay_1001',
            'amount' => $total,
            'currency' => 'EUR',
            'status' => PaymentStatus::Captured,
            'captured_at' => now()->subDays(3),
        ]);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1002(Store $store, Customer $customer, array $address, Product $hoodie): void
    {
        $hoodieVariant = $hoodie->variants->first();

        $subtotal = $hoodieVariant->price_amount * 1;
        $shipping = 499;
        $tax = (int) round($subtotal * 0.19);
        $total = $subtotal + $shipping + $tax;

        $order = Order::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => '1002',
            'email' => $customer->email,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'payment_method' => PaymentMethod::CreditCard,
            'currency' => 'EUR',
            'subtotal_amount' => $subtotal,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'shipping_address_json' => $address,
            'billing_address_json' => $address,
            'placed_at' => now()->subDays(7),
        ]);

        $orderLine = OrderLine::create([
            'order_id' => $order->id,
            'product_id' => $hoodie->id,
            'variant_id' => $hoodieVariant->id,
            'title_snapshot' => $hoodie->title,
            'variant_title_snapshot' => $hoodieVariant->title,
            'sku_snapshot' => $hoodieVariant->sku,
            'quantity' => 1,
            'unit_price_amount' => $hoodieVariant->price_amount,
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider' => 'mock',
            'provider_payment_id' => 'mock_pay_1002',
            'amount' => $total,
            'currency' => 'EUR',
            'status' => PaymentStatus::Captured,
            'captured_at' => now()->subDays(7),
        ]);

        $fulfillment = Fulfillment::create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'tracking_company' => 'DHL',
            'tracking_number' => '1234567890',
            'shipped_at' => now()->subDays(5),
            'delivered_at' => now()->subDays(2),
        ]);

        FulfillmentLine::create([
            'fulfillment_id' => $fulfillment->id,
            'order_line_id' => $orderLine->id,
            'quantity' => 1,
        ]);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1004(Store $store, Customer $customer, array $address, Product $polo): void
    {
        $poloVariant = $polo->variants->first();

        $subtotal = $poloVariant->price_amount * 1;
        $shipping = 499;
        $tax = (int) round($subtotal * 0.19);
        $total = $subtotal + $shipping + $tax;

        $order = Order::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => '1004',
            'email' => $customer->email,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => PaymentMethod::BankTransfer,
            'currency' => 'EUR',
            'subtotal_amount' => $subtotal,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'shipping_address_json' => $address,
            'billing_address_json' => $address,
            'placed_at' => now()->subDay(),
        ]);

        OrderLine::create([
            'order_id' => $order->id,
            'product_id' => $polo->id,
            'variant_id' => $poloVariant->id,
            'title_snapshot' => $polo->title,
            'variant_title_snapshot' => $poloVariant->title,
            'sku_snapshot' => $poloVariant->sku,
            'quantity' => 1,
            'unit_price_amount' => $poloVariant->price_amount,
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::BankTransfer,
            'provider' => 'mock',
            'provider_payment_id' => 'mock_pay_1004',
            'amount' => $total,
            'currency' => 'EUR',
            'status' => PaymentStatus::Pending,
        ]);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1005(Store $store, Customer $customer, array $address, Product $tshirt, Product $hoodie): void
    {
        $tshirtVariant = $tshirt->variants->first();
        $hoodieVariant = $hoodie->variants->first();

        $line1Subtotal = $tshirtVariant->price_amount * 1;
        $line2Subtotal = $hoodieVariant->price_amount * 1;
        $subtotal = $line1Subtotal + $line2Subtotal;
        $shipping = 499;
        $tax = (int) round($subtotal * 0.19);
        $total = $subtotal + $shipping + $tax;

        $order = Order::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => '1005',
            'email' => $customer->email,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'payment_method' => PaymentMethod::CreditCard,
            'currency' => 'EUR',
            'subtotal_amount' => $subtotal,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'shipping_address_json' => $address,
            'billing_address_json' => $address,
            'placed_at' => now()->subHours(6),
        ]);

        OrderLine::create([
            'order_id' => $order->id,
            'product_id' => $tshirt->id,
            'variant_id' => $tshirtVariant->id,
            'title_snapshot' => $tshirt->title,
            'variant_title_snapshot' => $tshirtVariant->title,
            'sku_snapshot' => $tshirtVariant->sku,
            'quantity' => 1,
            'unit_price_amount' => $tshirtVariant->price_amount,
            'subtotal_amount' => $line1Subtotal,
            'total_amount' => $line1Subtotal,
        ]);

        OrderLine::create([
            'order_id' => $order->id,
            'product_id' => $hoodie->id,
            'variant_id' => $hoodieVariant->id,
            'title_snapshot' => $hoodie->title,
            'variant_title_snapshot' => $hoodieVariant->title,
            'sku_snapshot' => $hoodieVariant->sku,
            'quantity' => 1,
            'unit_price_amount' => $hoodieVariant->price_amount,
            'subtotal_amount' => $line2Subtotal,
            'total_amount' => $line2Subtotal,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider' => 'mock',
            'provider_payment_id' => 'mock_pay_1005',
            'amount' => $total,
            'currency' => 'EUR',
            'status' => PaymentStatus::Captured,
            'captured_at' => now()->subHours(6),
        ]);
    }
}
