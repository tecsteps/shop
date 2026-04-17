<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Store;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $fashion = Store::where('handle', 'acme-fashion')->first();
        $electronics = Store::where('handle', 'acme-electronics')->first();

        $this->seedFashionOrders($fashion);
        $this->seedElectronicsOrders($electronics);
    }

    protected function seedFashionOrders(Store $store): void
    {
        $customers = Customer::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get()
            ->keyBy('email');

        $johnDoe = $customers['customer@acme.test'];
        $janeSmith = $customers['jane@example.com'];
        $michaelBrown = $customers['michael@example.com'];
        $sarahWilson = $customers['sarah@example.com'];
        $davidLee = $customers['david@example.com'];
        $emmaGarcia = $customers['emma@example.com'];
        $jamesTaylor = $customers['james@example.com'];
        $lisaAnderson = $customers['lisa@example.com'];
        $robertMartinez = $customers['robert@example.com'];
        $annaThomas = $customers['anna@example.com'];

        $johnAddress = $this->getDefaultAddress($johnDoe);
        $janeAddress = $this->getDefaultAddress($janeSmith);

        // Order #1001 - Awaiting fulfillment
        $variant = $this->findVariant($store, 'classic-cotton-t-shirt', ['S', 'White']);
        $order = $this->createOrder($store, $johnDoe, '#1001', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 4998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 798,
            'total_amount' => 5497,
            'placed_at' => now()->subDays(2),
        ], $johnAddress);
        $this->createOrderLine($order, $variant, 2, 2499, 4998);
        $this->createPayment($order, 'credit_card', 'mock_test_order1001', 'captured', 5497);

        // Order #1002 - Fully delivered
        $variant1 = $this->findVariant($store, 'organic-hoodie', ['M']);
        $variant2 = $this->findVariant($store, 'classic-cotton-t-shirt', ['L', 'Black']);
        $order = $this->createOrder($store, $johnDoe, '#1002', [
            'payment_method' => 'credit_card',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 8498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1357,
            'total_amount' => 8997,
            'placed_at' => now()->subDays(10),
        ], $johnAddress);
        $line1 = $this->createOrderLine($order, $variant1, 1, 5999, 5999);
        $line2 = $this->createOrderLine($order, $variant2, 1, 2499, 2499);
        $this->createPayment($order, 'credit_card', 'mock_test_order1002', 'captured', 8997);
        $fulfillment = $this->createFulfillment($order, 'delivered', 'DHL', 'DHL1234567890', now()->subDays(8));
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line1->id, 'quantity' => 1]);
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line2->id, 'quantity' => 1]);

        // Order #1003 - Partially fulfilled
        $variant1 = $this->findVariant($store, 'premium-slim-fit-jeans', ['32', 'Blue']);
        $variant2 = $this->findVariant($store, 'leather-belt', ['L/XL', 'Brown']);
        $order = $this->createOrder($store, $janeSmith, '#1003', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'partial',
            'subtotal_amount' => 11498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1836,
            'total_amount' => 11997,
            'placed_at' => now()->subDays(5),
        ], $janeAddress);
        $line1 = $this->createOrderLine($order, $variant1, 1, 7999, 7999);
        $this->createOrderLine($order, $variant2, 1, 3499, 3499);
        $this->createPayment($order, 'credit_card', 'mock_test_order1003', 'captured', 11997);
        $fulfillment = $this->createFulfillment($order, 'shipped', 'DHL', 'DHL9876543210', now()->subDays(3));
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line1->id, 'quantity' => 1]);

        // Order #1004 - Cancelled with full refund
        $variant = $this->findVariant($store, 'classic-cotton-t-shirt', ['M', 'Navy']);
        $order = $this->createOrder($store, $johnDoe, '#1004', [
            'payment_method' => 'credit_card',
            'status' => 'cancelled',
            'financial_status' => 'refunded',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 2499,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 399,
            'total_amount' => 2998,
            'placed_at' => now()->subDays(15),
        ], $johnAddress);
        $this->createOrderLine($order, $variant, 1, 2499, 2499);
        $payment = $this->createPayment($order, 'credit_card', 'mock_test_order1004', 'refunded', 2998);
        Refund::query()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 2998,
            'reason' => 'Customer requested cancellation',
            'status' => 'processed',
            'provider_refund_id' => 'mock_re_test_order1004',
        ]);

        // Order #1005 - Bank transfer awaiting payment
        $variant = $this->findVariant($store, 'leather-belt', ['S/M', 'Black']);
        $order = $this->createOrder($store, $janeSmith, '#1005', [
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'financial_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 3499,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 559,
            'total_amount' => 3998,
            'placed_at' => now()->subHours(2),
        ], $janeAddress);
        $this->createOrderLine($order, $variant, 1, 3499, 3499);
        $this->createPayment($order, 'bank_transfer', 'mock_test_order1005', 'pending', 3998);

        // Order #1006 - Standard paid order
        $michaelAddress = $this->getDefaultAddress($michaelBrown);
        $variant = $this->findVariant($store, 'running-sneakers', ['EU 42', 'Black']);
        $order = $this->createOrder($store, $michaelBrown, '#1006', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 11999,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1916,
            'total_amount' => 12498,
            'placed_at' => now()->subDay(),
        ], $michaelAddress);
        $this->createOrderLine($order, $variant, 1, 11999, 11999);
        $this->createPayment($order, 'credit_card', 'mock_test_order1006', 'captured', 12498);

        // Order #1007 - Multi-item delivered (PayPal)
        $sarahAddress = $this->getDefaultAddress($sarahWilson);
        $variant1 = $this->findVariant($store, 'v-neck-linen-tee', ['M', 'Beige']);
        $variant2 = $this->findVariant($store, 'wool-scarf', ['Grey']);
        $order = $this->createOrder($store, $sarahWilson, '#1007', [
            'payment_method' => 'paypal',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 9997,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1596,
            'total_amount' => 10496,
            'placed_at' => now()->subDays(20),
        ], $sarahAddress);
        $line1 = $this->createOrderLine($order, $variant1, 2, 3499, 6998);
        $line2 = $this->createOrderLine($order, $variant2, 1, 2999, 2999);
        $this->createPayment($order, 'paypal', 'mock_test_order1007', 'captured', 10496);
        $fulfillment = $this->createFulfillment($order, 'delivered', 'DHL', 'DHL1112223334', now()->subDays(18));
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line1->id, 'quantity' => 2]);
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line2->id, 'quantity' => 1]);

        // Order #1008 - Partial refund
        $davidAddress = $this->getDefaultAddress($davidLee);
        $variant1 = $this->findVariant($store, 'cargo-pants', ['32', 'Khaki']);
        $variant2 = $this->findVariant($store, 'graphic-print-tee', ['L']);
        $order = $this->createOrder($store, $davidLee, '#1008', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'partially_refunded',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 8498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1357,
            'total_amount' => 8997,
            'placed_at' => now()->subDays(12),
        ], $davidAddress);
        $line1 = $this->createOrderLine($order, $variant1, 1, 5499, 5499);
        $line2 = $this->createOrderLine($order, $variant2, 1, 2999, 2999);
        $payment = $this->createPayment($order, 'credit_card', 'mock_test_order1008', 'captured', 8997);
        $fulfillment = $this->createFulfillment($order, 'delivered', 'UPS', 'UPS5556667778', now()->subDays(10));
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line1->id, 'quantity' => 1]);
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line2->id, 'quantity' => 1]);
        Refund::query()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 2999,
            'reason' => 'Item returned',
            'status' => 'processed',
            'provider_refund_id' => 'mock_re_test_order1008',
        ]);

        // Order #1009 - Accessories order
        $emmaAddress = $this->getDefaultAddress($emmaGarcia);
        $variant1 = $this->findVariant($store, 'canvas-tote-bag', ['Natural']);
        $variant2 = $this->findVariant($store, 'bucket-hat', ['S/M', 'Black']);
        $order = $this->createOrder($store, $emmaGarcia, '#1009', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 4498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 718,
            'total_amount' => 4997,
            'placed_at' => now()->subDays(3),
        ], $emmaAddress);
        $this->createOrderLine($order, $variant1, 1, 1999, 1999);
        $this->createOrderLine($order, $variant2, 1, 2499, 2499);
        $this->createPayment($order, 'credit_card', 'mock_test_order1009', 'captured', 4997);

        // Order #1010 - High-value order (PayPal)
        $variant = $this->findVariant($store, 'cashmere-overcoat', ['M', 'Camel']);
        $order = $this->createOrder($store, $johnDoe, '#1010', [
            'payment_method' => 'paypal',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 49999,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 7983,
            'total_amount' => 50498,
            'placed_at' => now()->subDay(),
        ], $johnAddress);
        $this->createOrderLine($order, $variant, 1, 49999, 49999);
        $this->createPayment($order, 'paypal', 'mock_test_order1010', 'captured', 50498);

        // Order #1011 - Single item delivered
        $jamesAddress = $this->getDefaultAddress($jamesTaylor);
        $variant = $this->findVariant($store, 'striped-polo-shirt', ['XL']);
        $order = $this->createOrder($store, $jamesTaylor, '#1011', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 2799,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 447,
            'total_amount' => 3298,
            'placed_at' => now()->subDays(25),
        ], $jamesAddress);
        $line = $this->createOrderLine($order, $variant, 1, 2799, 2799);
        $this->createPayment($order, 'credit_card', 'mock_test_order1011', 'captured', 3298);
        $fulfillment = $this->createFulfillment($order, 'delivered', 'FedEx', 'FX9998887776', now()->subDays(23));
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line->id, 'quantity' => 1]);

        // Order #1012 - Multi-quantity order
        $lisaAddress = $this->getDefaultAddress($lisaAnderson);
        $variant = $this->findVariant($store, 'chino-shorts', ['34', 'Navy']);
        $order = $this->createOrder($store, $lisaAnderson, '#1012', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 7998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1277,
            'total_amount' => 8497,
            'placed_at' => now()->subDays(4),
        ], $lisaAddress);
        $this->createOrderLine($order, $variant, 2, 3999, 7998);
        $this->createPayment($order, 'credit_card', 'mock_test_order1012', 'captured', 8497);

        // Order #1013 - Multi-item order
        $robertAddress = $this->getDefaultAddress($robertMartinez);
        $variant1 = $this->findVariant($store, 'wide-leg-trousers', ['M']);
        $variant2 = $this->findVariant($store, 'wool-scarf', ['Burgundy']);
        $order = $this->createOrder($store, $robertMartinez, '#1013', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 7998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1277,
            'total_amount' => 8497,
            'placed_at' => now()->subDay(),
        ], $robertAddress);
        $this->createOrderLine($order, $variant1, 1, 4999, 4999);
        $this->createOrderLine($order, $variant2, 1, 2999, 2999);
        $this->createPayment($order, 'credit_card', 'mock_test_order1013', 'captured', 8497);

        // Order #1014 - Digital product order
        $annaAddress = $this->getDefaultAddress($annaThomas);
        $variant = $this->findVariant($store, 'gift-card', ['50 EUR']);
        $order = $this->createOrder($store, $annaThomas, '#1014', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 798,
            'total_amount' => 5000,
            'placed_at' => now()->subDays(14),
        ], $annaAddress);
        $line = $this->createOrderLine($order, $variant, 1, 5000, 5000);
        $this->createPayment($order, 'credit_card', 'mock_test_order1014', 'captured', 5000);
        $fulfillment = $this->createFulfillment($order, 'delivered', null, null, $order->placed_at);
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line->id, 'quantity' => 1]);

        // Order #1015 - Order with discount (Bank Transfer, confirmed)
        $variant1 = $this->findVariant($store, 'classic-cotton-t-shirt', ['M', 'White']);
        $variant2 = $this->findVariant($store, 'graphic-print-tee', ['M']);
        $discount = Discount::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('code', 'WELCOME10')
            ->first();

        $order = $this->createOrder($store, $johnDoe, '#1015', [
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 5498,
            'discount_amount' => 550,
            'shipping_amount' => 499,
            'tax_amount' => 790,
            'total_amount' => 5447,
            'placed_at' => now(),
        ], $johnAddress);

        $discountAllocation1 = $discount ? [['discount_id' => $discount->id, 'code' => 'WELCOME10', 'amount' => 250]] : [];
        $discountAllocation2 = $discount ? [['discount_id' => $discount->id, 'code' => 'WELCOME10', 'amount' => 300]] : [];

        $this->createOrderLine($order, $variant1, 1, 2499, 2499, null, $discountAllocation1);
        $this->createOrderLine($order, $variant2, 1, 2999, 2999, null, $discountAllocation2);
        $this->createPayment($order, 'bank_transfer', 'mock_test_order1015', 'captured', 5447);
    }

    protected function seedElectronicsOrders(Store $store): void
    {
        $customers = Customer::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->get()
            ->keyBy('email');

        $techFan = $customers['techfan@example.com'];
        $gadgetLover = $customers['gadgetlover@example.com'];
        $techFanAddress = $this->getDefaultAddress($techFan);
        $gadgetAddress = $this->getDefaultAddress($gadgetLover);

        // Order #5001 - Fulfilled
        $variant1 = $this->findVariant($store, 'pro-laptop-15', ['512GB']);
        $variant2 = $this->findVariant($store, 'usb-c-cable-2m', []);
        $order = $this->createOrder($store, $techFan, '#5001', [
            'payment_method' => 'credit_card',
            'status' => 'fulfilled',
            'financial_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'subtotal_amount' => 121298,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 19367,
            'total_amount' => 121298,
            'placed_at' => now()->subDays(7),
        ], $techFanAddress);
        $line1 = $this->createOrderLine($order, $variant1, 1, 119999, 119999);
        $line2 = $this->createOrderLine($order, $variant2, 1, 1299, 1299);
        $this->createPayment($order, 'credit_card', 'mock_test_order5001', 'captured', 121298);
        $fulfillment = $this->createFulfillment($order, 'delivered', 'DHL', 'DHL5551112233', now()->subDays(5));
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line1->id, 'quantity' => 1]);
        FulfillmentLine::query()->create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line2->id, 'quantity' => 1]);

        // Order #5002 - Unfulfilled
        $variant = $this->findVariant($store, 'wireless-headphones', ['Black']);
        $order = $this->createOrder($store, $gadgetLover, '#5002', [
            'payment_method' => 'credit_card',
            'status' => 'paid',
            'financial_status' => 'paid',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 14999,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 2395,
            'total_amount' => 14999,
            'placed_at' => now()->subDays(3),
        ], $gadgetAddress);
        $this->createOrderLine($order, $variant, 1, 14999, 14999);
        $this->createPayment($order, 'credit_card', 'mock_test_order5002', 'captured', 14999);

        // Order #5003 - Bank transfer pending
        $variant = $this->findVariant($store, 'monitor-stand', []);
        $order = $this->createOrder($store, $techFan, '#5003', [
            'payment_method' => 'bank_transfer',
            'status' => 'pending',
            'financial_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'subtotal_amount' => 4999,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 798,
            'total_amount' => 4999,
            'placed_at' => now()->subDay(),
        ], $techFanAddress);
        $this->createOrderLine($order, $variant, 1, 4999, 4999);
        $this->createPayment($order, 'bank_transfer', 'mock_test_order5003', 'pending', 4999);
    }

    /**
     * @param  string[]  $optionValues
     */
    protected function findVariant(Store $store, string $productHandle, array $optionValues): ProductVariant
    {
        $product = Product::query()->withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->where('handle', $productHandle)
            ->first();

        if (empty($optionValues)) {
            return ProductVariant::where('product_id', $product->id)
                ->where('is_default', true)
                ->first();
        }

        $query = ProductVariant::where('product_id', $product->id);

        foreach ($optionValues as $value) {
            $query->whereHas('optionValues', function ($q) use ($value) {
                $q->where('value', $value);
            });
        }

        return $query->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getDefaultAddress(Customer $customer): array
    {
        $address = CustomerAddress::where('customer_id', $customer->id)
            ->where('is_default', true)
            ->first();

        return $address ? $address->address_json : [];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $addressJson
     */
    protected function createOrder(Store $store, Customer $customer, string $orderNumber, array $attributes, array $addressJson): Order
    {
        return Order::query()->create(array_merge([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'currency' => 'EUR',
            'email' => $customer->email,
            'billing_address_json' => $addressJson,
            'shipping_address_json' => $addressJson,
        ], $attributes));
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $taxLines
     * @param  array<int, array<string, mixed>>|null  $discountAllocations
     */
    protected function createOrderLine(
        Order $order,
        ProductVariant $variant,
        int $quantity,
        int $unitPrice,
        int $totalAmount,
        ?array $taxLines = null,
        ?array $discountAllocations = null,
    ): OrderLine {
        return OrderLine::query()->create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'title_snapshot' => Product::query()->withoutGlobalScopes()->find($variant->product_id)->title,
            'sku_snapshot' => $variant->sku,
            'quantity' => $quantity,
            'unit_price_amount' => $unitPrice,
            'total_amount' => $totalAmount,
            'tax_lines_json' => $taxLines ?? [],
            'discount_allocations_json' => $discountAllocations ?? [],
        ]);
    }

    protected function createPayment(Order $order, string $method, string $providerPaymentId, string $status, int $amount): Payment
    {
        return Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'mock',
            'method' => $method,
            'provider_payment_id' => $providerPaymentId,
            'status' => $status,
            'amount' => $amount,
            'currency' => 'EUR',
        ]);
    }

    protected function createFulfillment(
        Order $order,
        string $status,
        ?string $trackingCompany,
        ?string $trackingNumber,
        mixed $shippedAt,
    ): Fulfillment {
        return Fulfillment::query()->create([
            'order_id' => $order->id,
            'status' => $status,
            'tracking_company' => $trackingCompany,
            'tracking_number' => $trackingNumber,
            'shipped_at' => $shippedAt,
        ]);
    }
}
