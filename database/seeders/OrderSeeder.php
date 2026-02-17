<?php

namespace Database\Seeders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
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
    private Store $fashionStore;

    private Store $electronicsStore;

    /** @var array<string, Customer> */
    private array $customers = [];

    /** @var array<string, Product> */
    private array $products = [];

    public function run(): void
    {
        $this->fashionStore = Store::query()->where('handle', 'acme-fashion')->firstOrFail();
        $this->electronicsStore = Store::query()->where('handle', 'acme-electronics')->firstOrFail();

        $this->loadCustomers();
        $this->loadProducts();

        $this->seedFashionOrders();
        $this->seedElectronicsOrders();
    }

    private function loadCustomers(): void
    {
        $fashionCustomers = Customer::query()
            ->where('store_id', $this->fashionStore->id)
            ->get();

        foreach ($fashionCustomers as $c) {
            $this->customers[$c->email] = $c;
        }

        $elecCustomers = Customer::query()
            ->where('store_id', $this->electronicsStore->id)
            ->get();

        foreach ($elecCustomers as $c) {
            $this->customers[$c->email] = $c;
        }
    }

    private function loadProducts(): void
    {
        $fashionProducts = Product::query()
            ->where('store_id', $this->fashionStore->id)
            ->with(['variants.optionValues'])
            ->get();

        foreach ($fashionProducts as $p) {
            $this->products[$p->handle] = $p;
        }

        $elecProducts = Product::query()
            ->where('store_id', $this->electronicsStore->id)
            ->with(['variants.optionValues'])
            ->get();

        foreach ($elecProducts as $p) {
            $this->products[$p->handle] = $p;
        }
    }

    private function seedFashionOrders(): void
    {
        $johnAddress = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company' => '',
            'address1' => 'Hauptstrasse 1',
            'address2' => '',
            'city' => 'Berlin',
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => '10115',
            'phone' => '+49 30 12345678',
        ];

        $janeAddress = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'company' => '',
            'address1' => 'Schillerstrasse 45',
            'address2' => '',
            'city' => 'Munich',
            'province' => 'Bavaria',
            'province_code' => 'BY',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => '80336',
            'phone' => '',
        ];

        // Order #1001 - Awaiting fulfillment
        $this->createOrder1001($johnAddress);
        // Order #1002 - Fully delivered
        $this->createOrder1002($johnAddress);
        // Order #1003 - Partially fulfilled
        $this->createOrder1003($janeAddress);
        // Order #1004 - Cancelled with full refund
        $this->createOrder1004($johnAddress);
        // Order #1005 - Bank transfer awaiting payment
        $this->createOrder1005($janeAddress);
        // Order #1006 - Standard paid order
        $this->createOrder1006();
        // Order #1007 - Multi-item delivered (PayPal)
        $this->createOrder1007();
        // Order #1008 - Partial refund
        $this->createOrder1008();
        // Order #1009 - Accessories order
        $this->createOrder1009();
        // Order #1010 - High-value order (PayPal)
        $this->createOrder1010($johnAddress);
        // Order #1011 - Single item delivered
        $this->createOrder1011();
        // Order #1012 - Multi-quantity order
        $this->createOrder1012();
        // Order #1013 - Multi-item order
        $this->createOrder1013();
        // Order #1014 - Digital product order
        $this->createOrder1014();
        // Order #1015 - Order with discount
        $this->createOrder1015($johnAddress);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1001(array $address): void
    {
        $customer = $this->customers['customer@acme.test'];
        $variant = $this->findVariant('classic-cotton-t-shirt', ['S', 'White']);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1001',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 4998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 798,
            'total_amount' => 5497,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDays(2),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['classic-cotton-t-shirt']->id,
            'variant_id' => $variant->id,
            'title_snapshot' => 'Classic Cotton T-Shirt',
            'sku_snapshot' => $variant->sku,
            'variant_title_snapshot' => 'S / White',
            'quantity' => 2,
            'unit_price_amount' => 2499,
            'subtotal_amount' => 4998,
            'tax_amount' => 798,
            'discount_amount' => 0,
            'total_amount' => 4998,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1001',
            'status' => PaymentStatus::Captured,
            'amount' => 5497,
        ]);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1002(array $address): void
    {
        $customer = $this->customers['customer@acme.test'];
        $hoodieVariant = $this->findVariant('organic-hoodie', ['M']);
        $tshirtVariant = $this->findVariant('classic-cotton-t-shirt', ['L', 'Black']);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1002',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'subtotal_amount' => 8498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1357,
            'total_amount' => 8997,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDays(10),
        ]);

        $line1 = OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['organic-hoodie']->id,
            'variant_id' => $hoodieVariant->id,
            'title_snapshot' => 'Organic Hoodie',
            'sku_snapshot' => $hoodieVariant->sku,
            'variant_title_snapshot' => 'M',
            'quantity' => 1,
            'unit_price_amount' => 5999,
            'subtotal_amount' => 5999,
            'tax_amount' => 958,
            'discount_amount' => 0,
            'total_amount' => 5999,
        ]);

        $line2 = OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['classic-cotton-t-shirt']->id,
            'variant_id' => $tshirtVariant->id,
            'title_snapshot' => 'Classic Cotton T-Shirt',
            'sku_snapshot' => $tshirtVariant->sku,
            'variant_title_snapshot' => 'L / Black',
            'quantity' => 1,
            'unit_price_amount' => 2499,
            'subtotal_amount' => 2499,
            'tax_amount' => 399,
            'discount_amount' => 0,
            'total_amount' => 2499,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1002',
            'status' => PaymentStatus::Captured,
            'amount' => 8997,
        ]);

        $fulfillment = Fulfillment::factory()->delivered()->create([
            'order_id' => $order->id,
            'tracking_company' => 'DHL',
            'tracking_number' => 'DHL1234567890',
            'tracking_url' => 'https://tracking.example.com/DHL1234567890',
            'shipped_at' => now()->subDays(8),
            'delivered_at' => now()->subDays(6),
        ]);

        FulfillmentLine::create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line1->id, 'quantity' => 1]);
        FulfillmentLine::create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line2->id, 'quantity' => 1]);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1003(array $address): void
    {
        $customer = $this->customers['jane@example.com'];
        $jeansVariant = $this->findVariant('premium-slim-fit-jeans', ['32', 'Blue']);
        $beltVariant = $this->findVariant('leather-belt', ['L/XL', 'Brown']);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1003',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Partial,
            'subtotal_amount' => 11498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1836,
            'total_amount' => 11997,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDays(5),
        ]);

        $jeansLine = OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['premium-slim-fit-jeans']->id,
            'variant_id' => $jeansVariant->id,
            'title_snapshot' => 'Premium Slim Fit Jeans',
            'sku_snapshot' => $jeansVariant->sku,
            'variant_title_snapshot' => '32 / Blue',
            'quantity' => 1,
            'unit_price_amount' => 7999,
            'subtotal_amount' => 7999,
            'tax_amount' => 1277,
            'discount_amount' => 0,
            'total_amount' => 7999,
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['leather-belt']->id,
            'variant_id' => $beltVariant->id,
            'title_snapshot' => 'Leather Belt',
            'sku_snapshot' => $beltVariant->sku,
            'variant_title_snapshot' => 'L/XL / Brown',
            'quantity' => 1,
            'unit_price_amount' => 3499,
            'subtotal_amount' => 3499,
            'tax_amount' => 559,
            'discount_amount' => 0,
            'total_amount' => 3499,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1003',
            'status' => PaymentStatus::Captured,
            'amount' => 11997,
        ]);

        $fulfillment = Fulfillment::factory()->shipped()->create([
            'order_id' => $order->id,
            'tracking_company' => 'DHL',
            'tracking_number' => 'DHL9876543210',
            'tracking_url' => 'https://tracking.example.com/DHL9876543210',
            'shipped_at' => now()->subDays(3),
        ]);

        FulfillmentLine::create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $jeansLine->id, 'quantity' => 1]);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1004(array $address): void
    {
        $customer = $this->customers['customer@acme.test'];
        $variant = $this->findVariant('classic-cotton-t-shirt', ['M', 'Navy']);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1004',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Cancelled,
            'financial_status' => FinancialStatus::Refunded,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 2499,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 399,
            'total_amount' => 2998,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'cancel_reason' => 'Customer requested cancellation',
            'cancelled_at' => now()->subDays(14),
            'placed_at' => now()->subDays(15),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['classic-cotton-t-shirt']->id,
            'variant_id' => $variant->id,
            'title_snapshot' => 'Classic Cotton T-Shirt',
            'sku_snapshot' => $variant->sku,
            'variant_title_snapshot' => 'M / Navy',
            'quantity' => 1,
            'unit_price_amount' => 2499,
            'subtotal_amount' => 2499,
            'tax_amount' => 399,
            'discount_amount' => 0,
            'total_amount' => 2499,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1004',
            'status' => PaymentStatus::Refunded,
            'amount' => 2998,
        ]);

        Refund::factory()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 2998,
            'reason' => 'Customer requested cancellation',
            'status' => 'processed',
            'provider_refund_id' => 'mock_re_test_order1004',
        ]);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1005(array $address): void
    {
        $customer = $this->customers['jane@example.com'];
        $variant = $this->findVariant('leather-belt', ['S/M', 'Black']);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1005',
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 3499,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 559,
            'total_amount' => 3998,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subHours(2),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['leather-belt']->id,
            'variant_id' => $variant->id,
            'title_snapshot' => 'Leather Belt',
            'sku_snapshot' => $variant->sku,
            'variant_title_snapshot' => 'S/M / Black',
            'quantity' => 1,
            'unit_price_amount' => 3499,
            'subtotal_amount' => 3499,
            'tax_amount' => 559,
            'discount_amount' => 0,
            'total_amount' => 3499,
        ]);

        Payment::factory()->pending()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::BankTransfer,
            'provider_payment_id' => 'mock_test_order1005',
            'amount' => 3998,
        ]);
    }

    private function createOrder1006(): void
    {
        $customer = $this->customers['michael@example.com'];
        $variant = $this->findVariant('running-sneakers', ['EU 42', 'Black']);
        $address = $this->getCustomerDefaultAddress($customer);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1006',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 11999,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1916,
            'total_amount' => 12498,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDay(),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['running-sneakers']->id,
            'variant_id' => $variant->id,
            'title_snapshot' => 'Running Sneakers',
            'sku_snapshot' => $variant->sku,
            'variant_title_snapshot' => 'EU 42 / Black',
            'quantity' => 1,
            'unit_price_amount' => 11999,
            'subtotal_amount' => 11999,
            'tax_amount' => 1916,
            'discount_amount' => 0,
            'total_amount' => 11999,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1006',
            'status' => PaymentStatus::Captured,
            'amount' => 12498,
        ]);
    }

    private function createOrder1007(): void
    {
        $customer = $this->customers['sarah@example.com'];
        $teeVariant = $this->findVariant('v-neck-linen-tee', ['M', 'Beige']);
        $scarfVariant = $this->findVariant('wool-scarf', ['Grey']);
        $address = $this->getCustomerDefaultAddress($customer);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1007',
            'payment_method' => PaymentMethod::Paypal,
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'subtotal_amount' => 9997,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1596,
            'total_amount' => 10496,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDays(20),
        ]);

        $line1 = OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['v-neck-linen-tee']->id,
            'variant_id' => $teeVariant->id,
            'title_snapshot' => 'V-Neck Linen Tee',
            'sku_snapshot' => $teeVariant->sku,
            'variant_title_snapshot' => 'M / Beige',
            'quantity' => 2,
            'unit_price_amount' => 3499,
            'subtotal_amount' => 6998,
            'tax_amount' => 1118,
            'discount_amount' => 0,
            'total_amount' => 6998,
        ]);

        $line2 = OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['wool-scarf']->id,
            'variant_id' => $scarfVariant->id,
            'title_snapshot' => 'Wool Scarf',
            'sku_snapshot' => $scarfVariant->sku,
            'variant_title_snapshot' => 'Grey',
            'quantity' => 1,
            'unit_price_amount' => 2999,
            'subtotal_amount' => 2999,
            'tax_amount' => 478,
            'discount_amount' => 0,
            'total_amount' => 2999,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::Paypal,
            'provider_payment_id' => 'mock_test_order1007',
            'status' => PaymentStatus::Captured,
            'amount' => 10496,
        ]);

        $fulfillment = Fulfillment::factory()->delivered()->create([
            'order_id' => $order->id,
            'tracking_company' => 'DHL',
            'tracking_number' => 'DHL1112223334',
            'tracking_url' => 'https://tracking.example.com/DHL1112223334',
            'shipped_at' => now()->subDays(18),
            'delivered_at' => now()->subDays(16),
        ]);

        FulfillmentLine::create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line1->id, 'quantity' => 2]);
        FulfillmentLine::create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line2->id, 'quantity' => 1]);
    }

    private function createOrder1008(): void
    {
        $customer = $this->customers['david@example.com'];
        $cargoVariant = $this->findVariant('cargo-pants', ['32', 'Khaki']);
        $graphicVariant = $this->findVariant('graphic-print-tee', ['L']);
        $address = $this->getCustomerDefaultAddress($customer);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1008',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::PartiallyRefunded,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'subtotal_amount' => 8498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1357,
            'total_amount' => 8997,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDays(12),
        ]);

        $line1 = OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['cargo-pants']->id,
            'variant_id' => $cargoVariant->id,
            'title_snapshot' => 'Cargo Pants',
            'sku_snapshot' => $cargoVariant->sku,
            'variant_title_snapshot' => '32 / Khaki',
            'quantity' => 1,
            'unit_price_amount' => 5499,
            'subtotal_amount' => 5499,
            'tax_amount' => 878,
            'discount_amount' => 0,
            'total_amount' => 5499,
        ]);

        $line2 = OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['graphic-print-tee']->id,
            'variant_id' => $graphicVariant->id,
            'title_snapshot' => 'Graphic Print Tee',
            'sku_snapshot' => $graphicVariant->sku,
            'variant_title_snapshot' => 'L',
            'quantity' => 1,
            'unit_price_amount' => 2999,
            'subtotal_amount' => 2999,
            'tax_amount' => 479,
            'discount_amount' => 0,
            'total_amount' => 2999,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1008',
            'status' => PaymentStatus::Captured,
            'amount' => 8997,
        ]);

        $fulfillment = Fulfillment::factory()->delivered()->create([
            'order_id' => $order->id,
            'tracking_company' => 'UPS',
            'tracking_number' => 'UPS5556667778',
            'tracking_url' => 'https://tracking.example.com/UPS5556667778',
            'shipped_at' => now()->subDays(10),
            'delivered_at' => now()->subDays(8),
        ]);

        FulfillmentLine::create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line1->id, 'quantity' => 1]);
        FulfillmentLine::create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line2->id, 'quantity' => 1]);

        Refund::factory()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 2999,
            'reason' => 'Item returned',
            'status' => 'processed',
            'provider_refund_id' => 'mock_re_test_order1008',
        ]);
    }

    private function createOrder1009(): void
    {
        $customer = $this->customers['emma@example.com'];
        $toteVariant = $this->findVariant('canvas-tote-bag', ['Natural']);
        $hatVariant = $this->findVariant('bucket-hat', ['S/M', 'Black']);
        $address = $this->getCustomerDefaultAddress($customer);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1009',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 4498,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 718,
            'total_amount' => 4997,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDays(3),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['canvas-tote-bag']->id,
            'variant_id' => $toteVariant->id,
            'title_snapshot' => 'Canvas Tote Bag',
            'sku_snapshot' => $toteVariant->sku,
            'variant_title_snapshot' => 'Natural',
            'quantity' => 1,
            'unit_price_amount' => 1999,
            'subtotal_amount' => 1999,
            'tax_amount' => 319,
            'discount_amount' => 0,
            'total_amount' => 1999,
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['bucket-hat']->id,
            'variant_id' => $hatVariant->id,
            'title_snapshot' => 'Bucket Hat',
            'sku_snapshot' => $hatVariant->sku,
            'variant_title_snapshot' => 'S/M / Black',
            'quantity' => 1,
            'unit_price_amount' => 2499,
            'subtotal_amount' => 2499,
            'tax_amount' => 399,
            'discount_amount' => 0,
            'total_amount' => 2499,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1009',
            'status' => PaymentStatus::Captured,
            'amount' => 4997,
        ]);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1010(array $address): void
    {
        $customer = $this->customers['customer@acme.test'];
        $variant = $this->findVariant('cashmere-overcoat', ['M', 'Camel']);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1010',
            'payment_method' => PaymentMethod::Paypal,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 49999,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 7983,
            'total_amount' => 50498,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDay(),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['cashmere-overcoat']->id,
            'variant_id' => $variant->id,
            'title_snapshot' => 'Cashmere Overcoat',
            'sku_snapshot' => $variant->sku,
            'variant_title_snapshot' => 'M / Camel',
            'quantity' => 1,
            'unit_price_amount' => 49999,
            'subtotal_amount' => 49999,
            'tax_amount' => 7983,
            'discount_amount' => 0,
            'total_amount' => 49999,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::Paypal,
            'provider_payment_id' => 'mock_test_order1010',
            'status' => PaymentStatus::Captured,
            'amount' => 50498,
        ]);
    }

    private function createOrder1011(): void
    {
        $customer = $this->customers['james@example.com'];
        $variant = $this->findVariant('striped-polo-shirt', ['XL']);
        $address = $this->getCustomerDefaultAddress($customer);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1011',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'subtotal_amount' => 2799,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 447,
            'total_amount' => 3298,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDays(25),
        ]);

        $line = OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['striped-polo-shirt']->id,
            'variant_id' => $variant->id,
            'title_snapshot' => 'Striped Polo Shirt',
            'sku_snapshot' => $variant->sku,
            'variant_title_snapshot' => 'XL',
            'quantity' => 1,
            'unit_price_amount' => 2799,
            'subtotal_amount' => 2799,
            'tax_amount' => 447,
            'discount_amount' => 0,
            'total_amount' => 2799,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1011',
            'status' => PaymentStatus::Captured,
            'amount' => 3298,
        ]);

        $fulfillment = Fulfillment::factory()->delivered()->create([
            'order_id' => $order->id,
            'tracking_company' => 'FedEx',
            'tracking_number' => 'FX9998887776',
            'tracking_url' => 'https://tracking.example.com/FX9998887776',
            'shipped_at' => now()->subDays(23),
            'delivered_at' => now()->subDays(21),
        ]);

        FulfillmentLine::create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line->id, 'quantity' => 1]);
    }

    private function createOrder1012(): void
    {
        $customer = $this->customers['lisa@example.com'];
        $variant = $this->findVariant('chino-shorts', ['34', 'Navy']);
        $address = $this->getCustomerDefaultAddress($customer);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1012',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 7998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1277,
            'total_amount' => 8497,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDays(4),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['chino-shorts']->id,
            'variant_id' => $variant->id,
            'title_snapshot' => 'Chino Shorts',
            'sku_snapshot' => $variant->sku,
            'variant_title_snapshot' => '34 / Navy',
            'quantity' => 2,
            'unit_price_amount' => 3999,
            'subtotal_amount' => 7998,
            'tax_amount' => 1277,
            'discount_amount' => 0,
            'total_amount' => 7998,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1012',
            'status' => PaymentStatus::Captured,
            'amount' => 8497,
        ]);
    }

    private function createOrder1013(): void
    {
        $customer = $this->customers['robert@example.com'];
        $trouserVariant = $this->findVariant('wide-leg-trousers', ['M']);
        $scarfVariant = $this->findVariant('wool-scarf', ['Burgundy']);
        $address = $this->getCustomerDefaultAddress($customer);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1013',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 7998,
            'discount_amount' => 0,
            'shipping_amount' => 499,
            'tax_amount' => 1277,
            'total_amount' => 8497,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDay(),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['wide-leg-trousers']->id,
            'variant_id' => $trouserVariant->id,
            'title_snapshot' => 'Wide Leg Trousers',
            'sku_snapshot' => $trouserVariant->sku,
            'variant_title_snapshot' => 'M',
            'quantity' => 1,
            'unit_price_amount' => 4999,
            'subtotal_amount' => 4999,
            'tax_amount' => 798,
            'discount_amount' => 0,
            'total_amount' => 4999,
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['wool-scarf']->id,
            'variant_id' => $scarfVariant->id,
            'title_snapshot' => 'Wool Scarf',
            'sku_snapshot' => $scarfVariant->sku,
            'variant_title_snapshot' => 'Burgundy',
            'quantity' => 1,
            'unit_price_amount' => 2999,
            'subtotal_amount' => 2999,
            'tax_amount' => 479,
            'discount_amount' => 0,
            'total_amount' => 2999,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1013',
            'status' => PaymentStatus::Captured,
            'amount' => 8497,
        ]);
    }

    private function createOrder1014(): void
    {
        $customer = $this->customers['anna@example.com'];
        $variant = $this->findVariant('gift-card', ['50 EUR']);
        $address = $this->getCustomerDefaultAddress($customer);

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1014',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 798,
            'total_amount' => 5000,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now()->subDays(14),
        ]);

        $line = OrderLine::factory()->digital()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['gift-card']->id,
            'variant_id' => $variant->id,
            'title_snapshot' => 'Gift Card',
            'sku_snapshot' => $variant->sku,
            'variant_title_snapshot' => '50 EUR',
            'quantity' => 1,
            'unit_price_amount' => 5000,
            'subtotal_amount' => 5000,
            'tax_amount' => 798,
            'discount_amount' => 0,
            'total_amount' => 5000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order1014',
            'status' => PaymentStatus::Captured,
            'amount' => 5000,
        ]);

        $fulfillment = Fulfillment::factory()->delivered()->create([
            'order_id' => $order->id,
            'status' => FulfillmentShipmentStatus::Delivered,
            'tracking_company' => null,
            'tracking_number' => null,
            'tracking_url' => null,
            'shipped_at' => now()->subDays(14),
            'delivered_at' => now()->subDays(14),
        ]);

        FulfillmentLine::create(['fulfillment_id' => $fulfillment->id, 'order_line_id' => $line->id, 'quantity' => 1]);
    }

    /**
     * @param  array<string, string>  $address
     */
    private function createOrder1015(array $address): void
    {
        $customer = $this->customers['customer@acme.test'];
        $tshirtVariant = $this->findVariant('classic-cotton-t-shirt', ['M', 'White']);
        $graphicVariant = $this->findVariant('graphic-print-tee', ['M']);

        $discount = Discount::query()
            ->where('store_id', $this->fashionStore->id)
            ->where('code', 'WELCOME10')
            ->firstOrFail();

        $order = Order::factory()->create([
            'store_id' => $this->fashionStore->id,
            'customer_id' => $customer->id,
            'order_number' => '#1015',
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 5498,
            'discount_amount' => 550,
            'discount_code' => 'WELCOME10',
            'shipping_amount' => 499,
            'tax_amount' => 790,
            'total_amount' => 5447,
            'email' => $customer->email,
            'billing_address_json' => $address,
            'shipping_address_json' => $address,
            'placed_at' => now(),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['classic-cotton-t-shirt']->id,
            'variant_id' => $tshirtVariant->id,
            'title_snapshot' => 'Classic Cotton T-Shirt',
            'sku_snapshot' => $tshirtVariant->sku,
            'variant_title_snapshot' => 'M / White',
            'quantity' => 1,
            'unit_price_amount' => 2499,
            'subtotal_amount' => 2499,
            'tax_amount' => 395,
            'discount_amount' => 250,
            'total_amount' => 2249,
            'discount_allocations_json' => [
                ['discount_id' => $discount->id, 'code' => 'WELCOME10', 'amount' => 250],
            ],
        ]);

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'product_id' => $this->products['graphic-print-tee']->id,
            'variant_id' => $graphicVariant->id,
            'title_snapshot' => 'Graphic Print Tee',
            'sku_snapshot' => $graphicVariant->sku,
            'variant_title_snapshot' => 'M',
            'quantity' => 1,
            'unit_price_amount' => 2999,
            'subtotal_amount' => 2999,
            'tax_amount' => 395,
            'discount_amount' => 300,
            'total_amount' => 2699,
            'discount_allocations_json' => [
                ['discount_id' => $discount->id, 'code' => 'WELCOME10', 'amount' => 300],
            ],
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'method' => PaymentMethod::BankTransfer,
            'provider_payment_id' => 'mock_test_order1015',
            'status' => PaymentStatus::Captured,
            'amount' => 5447,
        ]);
    }

    private function seedElectronicsOrders(): void
    {
        $techfan = $this->customers['techfan@example.com'];
        $gadgetlover = $this->customers['gadgetlover@example.com'];
        $techAddress = $this->getCustomerDefaultAddress($techfan);
        $gadgetAddress = $this->getCustomerDefaultAddress($gadgetlover);

        // Order #5001
        $laptopVariant = $this->findVariant('pro-laptop-15', ['512GB']);
        $cableVariant = $this->findVariant('usb-c-cable-2m', []);

        $order5001 = Order::factory()->create([
            'store_id' => $this->electronicsStore->id,
            'customer_id' => $techfan->id,
            'order_number' => '#5001',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Fulfilled,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'subtotal_amount' => 121298,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 19381,
            'total_amount' => 121298,
            'email' => $techfan->email,
            'billing_address_json' => $techAddress,
            'shipping_address_json' => $techAddress,
            'placed_at' => now()->subDays(7),
        ]);

        $line1 = OrderLine::factory()->create([
            'order_id' => $order5001->id,
            'product_id' => $this->products['pro-laptop-15']->id,
            'variant_id' => $laptopVariant->id,
            'title_snapshot' => 'Pro Laptop 15',
            'sku_snapshot' => $laptopVariant->sku,
            'variant_title_snapshot' => '512GB',
            'quantity' => 1,
            'unit_price_amount' => 119999,
            'subtotal_amount' => 119999,
            'total_amount' => 119999,
        ]);

        $line2 = OrderLine::factory()->create([
            'order_id' => $order5001->id,
            'product_id' => $this->products['usb-c-cable-2m']->id,
            'variant_id' => $cableVariant->id,
            'title_snapshot' => 'USB-C Cable 2m',
            'sku_snapshot' => $cableVariant->sku,
            'quantity' => 1,
            'unit_price_amount' => 1299,
            'subtotal_amount' => 1299,
            'total_amount' => 1299,
        ]);

        Payment::factory()->create([
            'order_id' => $order5001->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order5001',
            'status' => PaymentStatus::Captured,
            'amount' => 121298,
        ]);

        $f = Fulfillment::factory()->delivered()->create([
            'order_id' => $order5001->id,
            'tracking_company' => 'DHL',
            'tracking_number' => 'DHL5001001001',
            'shipped_at' => now()->subDays(5),
            'delivered_at' => now()->subDays(3),
        ]);
        FulfillmentLine::create(['fulfillment_id' => $f->id, 'order_line_id' => $line1->id, 'quantity' => 1]);
        FulfillmentLine::create(['fulfillment_id' => $f->id, 'order_line_id' => $line2->id, 'quantity' => 1]);

        // Order #5002
        $headphonesVariant = $this->findVariant('wireless-headphones', ['Black']);

        $order5002 = Order::factory()->create([
            'store_id' => $this->electronicsStore->id,
            'customer_id' => $gadgetlover->id,
            'order_number' => '#5002',
            'payment_method' => PaymentMethod::CreditCard,
            'status' => OrderStatus::Paid,
            'financial_status' => FinancialStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 14999,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 2396,
            'total_amount' => 14999,
            'email' => $gadgetlover->email,
            'billing_address_json' => $gadgetAddress,
            'shipping_address_json' => $gadgetAddress,
            'placed_at' => now()->subDays(2),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order5002->id,
            'product_id' => $this->products['wireless-headphones']->id,
            'variant_id' => $headphonesVariant->id,
            'title_snapshot' => 'Wireless Headphones',
            'sku_snapshot' => $headphonesVariant->sku,
            'variant_title_snapshot' => 'Black',
            'quantity' => 1,
            'unit_price_amount' => 14999,
            'subtotal_amount' => 14999,
            'total_amount' => 14999,
        ]);

        Payment::factory()->create([
            'order_id' => $order5002->id,
            'method' => PaymentMethod::CreditCard,
            'provider_payment_id' => 'mock_test_order5002',
            'status' => PaymentStatus::Captured,
            'amount' => 14999,
        ]);

        // Order #5003 - Bank transfer pending
        $standVariant = $this->findVariant('monitor-stand', []);

        $order5003 = Order::factory()->create([
            'store_id' => $this->electronicsStore->id,
            'customer_id' => $techfan->id,
            'order_number' => '#5003',
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => OrderStatus::Pending,
            'financial_status' => FinancialStatus::Pending,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
            'subtotal_amount' => 4999,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 799,
            'total_amount' => 4999,
            'email' => $techfan->email,
            'billing_address_json' => $techAddress,
            'shipping_address_json' => $techAddress,
            'placed_at' => now()->subDay(),
        ]);

        OrderLine::factory()->create([
            'order_id' => $order5003->id,
            'product_id' => $this->products['monitor-stand']->id,
            'variant_id' => $standVariant->id,
            'title_snapshot' => 'Monitor Stand',
            'sku_snapshot' => $standVariant->sku,
            'quantity' => 1,
            'unit_price_amount' => 4999,
            'subtotal_amount' => 4999,
            'total_amount' => 4999,
        ]);

        Payment::factory()->pending()->create([
            'order_id' => $order5003->id,
            'method' => PaymentMethod::BankTransfer,
            'provider_payment_id' => 'mock_test_order5003',
            'amount' => 4999,
        ]);
    }

    /**
     * @param  list<string>  $optionValues
     */
    private function findVariant(string $productHandle, array $optionValues): ProductVariant
    {
        $product = $this->products[$productHandle] ?? null;

        if (! $product) {
            throw new \RuntimeException("Product not found: {$productHandle}");
        }

        if (empty($optionValues)) {
            // Return the default variant
            return $product->variants->firstWhere('is_default', true)
                ?? $product->variants->first();
        }

        foreach ($product->variants as $variant) {
            $variantValues = $variant->optionValues->pluck('value')->sort()->values()->toArray();
            $requestedValues = collect($optionValues)->sort()->values()->toArray();

            if ($variantValues === $requestedValues) {
                return $variant;
            }
        }

        throw new \RuntimeException("Variant not found for {$productHandle} with options: ".implode(', ', $optionValues));
    }

    /**
     * @return array<string, string>
     */
    private function getCustomerDefaultAddress(Customer $customer): array
    {
        $address = $customer->addresses()->where('is_default', true)->first();

        if ($address) {
            return $address->address_json;
        }

        // Fallback
        $parts = explode(' ', $customer->name, 2);

        return [
            'first_name' => $parts[0],
            'last_name' => $parts[1] ?? '',
            'company' => '',
            'address1' => 'Musterstrasse 1',
            'address2' => '',
            'city' => 'Berlin',
            'province' => '',
            'province_code' => '',
            'country' => 'Germany',
            'country_code' => 'DE',
            'zip' => '10115',
            'phone' => '',
        ];
    }
}
