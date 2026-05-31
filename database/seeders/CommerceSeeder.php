<?php

namespace Database\Seeders;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\FinancialStatus;
use App\Enums\FulfillmentShipmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Models\AnalyticsDaily;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds commerce configuration and demo orders for the demo store.
 *
 * Adds shipping zones + rates, tax settings, a handful of discounts, and a few
 * sample orders spanning paid, fulfilled, and pending bank-transfer statuses so
 * the admin order views have realistic data. Re-running is safe: each record is
 * keyed and skipped if already present.
 *
 * Depends on {@see DemoStoreSeeder} (the store) and {@see CatalogSeeder}
 * (variants to order). When no variants exist the order seeding is skipped.
 */
class CommerceSeeder extends Seeder
{
    public function __construct(private readonly OrderService $orders) {}

    public function run(): void
    {
        $store = (new DemoStoreSeeder)->store();
        app()->instance('current_store', $store);

        $this->seedShipping($store);
        $this->seedTax($store);
        $this->seedDiscounts($store);
        $customers = $this->seedCustomers($store);
        $this->seedOrders($store, $customers);
        $this->seedAnalytics($store);
    }

    /**
     * Seed ~30 days of pre-aggregated daily analytics so the admin Analytics
     * page shows a populated chart, KPI tiles, and a non-trivial conversion
     * funnel for the demo.
     *
     * These rows are what {@see \App\Services\AnalyticsService::getDailyMetrics}
     * and ::summarize read; in production the {@see \App\Jobs\AggregateAnalytics}
     * job rolls them up from events, but the demo store has no event history, so
     * we synthesise a believable curve (a gentle weekly rhythm with a mild
     * upward trend) plus a funnel that narrows visits -> add-to-cart ->
     * checkout-started -> checkout-completed. Idempotent: skipped if rows exist.
     */
    private function seedAnalytics(Store $store): void
    {
        if (AnalyticsDaily::query()->where('store_id', $store->id)->exists()) {
            return;
        }

        $days = 30;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            // Weekly rhythm (weekends busier) + slight upward trend over the month.
            $weekendBoost = in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true) ? 1.4 : 1.0;
            $trend = 1 + (($days - 1 - $i) / $days) * 0.5;

            $visits = (int) round(random_int(180, 320) * $weekendBoost * $trend);
            $addToCart = (int) round($visits * (random_int(28, 38) / 100));
            $checkoutStarted = (int) round($addToCart * (random_int(45, 60) / 100));
            $checkoutCompleted = (int) round($checkoutStarted * (random_int(55, 75) / 100));
            $orders = max(0, $checkoutCompleted);

            // ~$45-$140 average order value, in cents.
            $aov = random_int(4500, 14000);
            $revenue = $orders * $aov;

            AnalyticsDaily::create([
                'store_id' => $store->id,
                'date' => $date->toDateString(),
                'orders_count' => $orders,
                'revenue_amount' => $revenue,
                'aov_amount' => $orders > 0 ? intdiv($revenue, $orders) : 0,
                'visits_count' => $visits,
                'add_to_cart_count' => $addToCart,
                'checkout_started_count' => $checkoutStarted,
                'checkout_completed_count' => $checkoutCompleted,
            ]);
        }
    }

    /**
     * Seed a handful of named customers (besides the demo customer) each with a
     * saved default address, so the admin Customers views and customer order
     * history are populated. Returns them keyed by email.
     *
     * @return array<string, Customer>
     */
    private function seedCustomers(Store $store): array
    {
        $blueprints = [
            ['email' => DemoStoreSeeder::CUSTOMER_EMAIL, 'name' => 'Demo Customer', 'city' => 'Springfield', 'province' => 'IL', 'postal' => '62701', 'country' => 'US', 'line1' => '123 Main St'],
            ['email' => 'jane.smith@example.com', 'name' => 'Jane Smith', 'city' => 'Portland', 'province' => 'OR', 'postal' => '97201', 'country' => 'US', 'line1' => '88 Rose Ave'],
            ['email' => 'liam.mueller@example.de', 'name' => 'Liam Müller', 'city' => 'Berlin', 'province' => null, 'postal' => '10115', 'country' => 'DE', 'line1' => 'Hauptstrasse 5'],
            ['email' => 'aisha.khan@example.com', 'name' => 'Aisha Khan', 'city' => 'Austin', 'province' => 'TX', 'postal' => '73301', 'country' => 'US', 'line1' => '400 Congress Ave'],
        ];

        $customers = [];

        foreach ($blueprints as $data) {
            $customer = Customer::withoutGlobalScopes()->firstOrCreate(
                ['store_id' => $store->id, 'email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password_hash' => Hash::make('password'),
                    'marketing_opt_in' => true,
                ],
            );

            if ($customer->addresses()->count() === 0) {
                $customer->addresses()->create([
                    'label' => 'Home',
                    'is_default' => true,
                    'address_json' => [
                        'first_name' => Str::before($data['name'], ' '),
                        'last_name' => Str::after($data['name'], ' '),
                        'address1' => $data['line1'],
                        'city' => $data['city'],
                        'province_code' => $data['province'],
                        'postal_code' => $data['postal'],
                        'country' => $data['country'],
                    ],
                ]);
            }

            $customers[$data['email']] = $customer;
        }

        return $customers;
    }

    private function seedShipping(Store $store): void
    {
        if (ShippingZone::query()->where('store_id', $store->id)->exists()) {
            return;
        }

        $domestic = ShippingZone::create([
            'store_id' => $store->id,
            'name' => 'Domestic (US)',
            'countries_json' => ['US'],
            'regions_json' => [],
        ]);

        ShippingRate::create([
            'zone_id' => $domestic->id,
            'name' => 'Standard',
            'type' => ShippingRateType::Flat->value,
            'config_json' => ['amount' => 599],
            'is_active' => true,
        ]);
        ShippingRate::create([
            'zone_id' => $domestic->id,
            'name' => 'Free over $75',
            'type' => ShippingRateType::Price->value,
            'config_json' => ['ranges' => [
                ['min_amount' => 0, 'max_amount' => 7499, 'amount' => 599],
                ['min_amount' => 7500, 'amount' => 0],
            ]],
            'is_active' => true,
        ]);

        $international = ShippingZone::create([
            'store_id' => $store->id,
            'name' => 'Europe',
            'countries_json' => ['DE', 'FR', 'AT', 'CH', 'NL'],
            'regions_json' => [],
        ]);

        ShippingRate::create([
            'zone_id' => $international->id,
            'name' => 'International Weight',
            'type' => ShippingRateType::Weight->value,
            'config_json' => ['ranges' => [
                ['min_g' => 0, 'max_g' => 1000, 'amount' => 1299],
                ['min_g' => 1001, 'max_g' => 5000, 'amount' => 2499],
            ]],
            'is_active' => true,
        ]);
    }

    private function seedTax(Store $store): void
    {
        TaxSettings::firstOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => TaxMode::Manual->value,
                'provider' => 'none',
                'prices_include_tax' => false,
                'config_json' => ['default_rate' => 800, 'name' => 'Sales Tax'],
            ],
        );
    }

    private function seedDiscounts(Store $store): void
    {
        $discounts = [
            [
                'code' => 'WELCOME10',
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::Percent,
                'value_amount' => 10,
                'rules_json' => [],
            ],
            [
                'code' => 'SAVE5',
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::Fixed,
                'value_amount' => 500,
                'rules_json' => ['min_purchase_amount' => 5000],
            ],
            [
                'code' => 'FREESHIP',
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::FreeShipping,
                'value_amount' => 0,
                'rules_json' => [],
            ],
        ];

        foreach ($discounts as $data) {
            Discount::firstOrCreate(
                ['store_id' => $store->id, 'code' => $data['code']],
                [
                    'type' => $data['type']->value,
                    'value_type' => $data['value_type']->value,
                    'value_amount' => $data['value_amount'],
                    'starts_at' => Carbon::now()->subWeek(),
                    'ends_at' => Carbon::now()->addYear(),
                    'usage_limit' => null,
                    'usage_count' => 0,
                    'rules_json' => $data['rules_json'],
                    'status' => DiscountStatus::Active->value,
                ],
            );
        }
    }

    /**
     * Seed a spread of orders covering every financial/fulfillment status so the
     * admin Orders views and customer order history are fully demoable:
     * pending, paid (unfulfilled), partially fulfilled, fully fulfilled,
     * cancelled, partially refunded, fully refunded, and a bank-transfer order
     * still pending payment confirmation.
     *
     * @param  array<string, Customer>  $customers
     */
    private function seedOrders(Store $store, array $customers): void
    {
        if (Order::query()->where('store_id', $store->id)->exists()) {
            return;
        }

        $variants = ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->where('store_id', $store->id))
            ->with('product', 'inventoryItem')
            ->take(12)
            ->get();

        if ($variants->isEmpty()) {
            return; // No catalog seeded yet; nothing to order.
        }

        $emails = array_keys($customers);
        $chunks = $variants->chunk(2)->values();
        $pick = fn (int $i) => $chunks->get($i % $chunks->count());
        $forCustomer = fn (int $i): ?Customer => $customers[$emails[$i % count($emails)]] ?? null;

        // 1. Paid, unfulfilled (credit card).
        $this->makeOrder($store, $pick(0), PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, PaymentStatus::Captured, $forCustomer(0));

        // 2. Pending (credit card authorised but not captured).
        $this->makeOrder($store, $pick(1), PaymentMethod::CreditCard, OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled, PaymentStatus::Pending, $forCustomer(1));

        // 3. Bank transfer pending — exercises the admin "Confirm Payment" action.
        $this->makeOrder($store, $pick(2), PaymentMethod::BankTransfer, OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled, PaymentStatus::Pending, $forCustomer(2));

        // 4. Fully fulfilled + delivered.
        $full = $this->makeOrder($store, $pick(3), PaymentMethod::Paypal, OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, PaymentStatus::Captured, $forCustomer(3));
        $this->fulfill($full, $full->lines, FulfillmentShipmentStatus::Delivered, daysAgoShipped: 4, daysAgoDelivered: 1);

        // 5. Partially fulfilled (one line shipped, one line outstanding).
        $partial = $this->makeOrder($store, $pick(4), PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Partial, PaymentStatus::Captured, $forCustomer(0), multiLine: true);
        $this->fulfill($partial, $partial->lines->take(1), FulfillmentShipmentStatus::Shipped, daysAgoShipped: 1, daysAgoDelivered: null);

        // 6. Cancelled.
        $this->makeOrder($store, $pick(5), PaymentMethod::CreditCard, OrderStatus::Cancelled, FinancialStatus::Voided, FulfillmentStatus::Unfulfilled, PaymentStatus::Failed, $forCustomer(1));

        // 7. Partially refunded (refund part of a paid order).
        $partRefund = $this->makeOrder($store, $pick(0), PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::PartiallyRefunded, FulfillmentStatus::Fulfilled, PaymentStatus::Captured, $forCustomer(2));
        $this->fulfill($partRefund, $partRefund->lines, FulfillmentShipmentStatus::Delivered, daysAgoShipped: 6, daysAgoDelivered: 3);
        $this->refund($partRefund, (int) round($partRefund->total_amount * 0.4), 'Customer returned one item.');

        // 8. Fully refunded.
        $fullRefund = $this->makeOrder($store, $pick(1), PaymentMethod::Paypal, OrderStatus::Refunded, FinancialStatus::Refunded, FulfillmentStatus::Unfulfilled, PaymentStatus::Refunded, $forCustomer(3));
        $this->refund($fullRefund, $fullRefund->total_amount, 'Order cancelled after payment; full refund.');
    }

    /**
     * Create a fulfillment shipment covering the given lines and recompute the
     * order's fulfillment status from what shipped.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\OrderLine>  $lines
     */
    private function fulfill(Order $order, $lines, FulfillmentShipmentStatus $status, int $daysAgoShipped, ?int $daysAgoDelivered): void
    {
        $shipment = $order->fulfillments()->create([
            'status' => $status->value,
            'tracking_company' => 'DHL',
            'tracking_number' => Str::upper(Str::random(10)),
            'tracking_url' => 'https://tracking.example.com/'.Str::upper(Str::random(10)),
            'shipped_at' => Carbon::now()->subDays($daysAgoShipped),
            'delivered_at' => $daysAgoDelivered !== null ? Carbon::now()->subDays($daysAgoDelivered) : null,
        ]);

        foreach ($lines as $line) {
            $shipment->lines()->create(['order_line_id' => $line->id, 'quantity' => $line->quantity]);
        }
    }

    /**
     * Record a processed refund against the order's payment for demo purposes.
     */
    private function refund(Order $order, int $amount, string $reason): void
    {
        $payment = $order->payments()->first();

        if ($payment === null) {
            return;
        }

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => RefundStatus::Processed->value,
            'provider_refund_id' => 'mock_re_'.Str::lower(Str::random(20)),
        ]);
    }

    /**
     * Create a demo order with snapshot lines and a payment.
     *
     * @param  \Illuminate\Support\Collection<int, ProductVariant>  $variants
     */
    private function makeOrder(
        Store $store,
        $variants,
        PaymentMethod $method,
        OrderStatus $status,
        FinancialStatus $financial,
        FulfillmentStatus $fulfillment,
        PaymentStatus $paymentStatus,
        ?Customer $customer = null,
        bool $multiLine = false,
    ): Order {
        $subtotal = 0;
        $address = $customer?->addresses()->first()?->address_json;

        $order = Order::create([
            'store_id' => $store->id,
            'customer_id' => $customer?->id,
            'order_number' => $this->orders->generateOrderNumber($store),
            'payment_method' => $method->value,
            'status' => $status->value,
            'financial_status' => $financial->value,
            'fulfillment_status' => $fulfillment->value,
            'currency' => $store->default_currency,
            'subtotal_amount' => 0,
            'shipping_amount' => 599,
            'tax_amount' => 0,
            'total_amount' => 0,
            'email' => $customer?->email ?? DemoStoreSeeder::CUSTOMER_EMAIL,
            'shipping_address_json' => $address,
            'billing_address_json' => $address,
            'placed_at' => Carbon::now()->subDays(random_int(1, 14)),
        ]);

        // Ensure partially-fulfilled demo orders have at least two lines.
        $orderVariants = $multiLine && $variants->count() < 2
            ? $variants->concat($variants->take(1))
            : $variants;

        foreach ($orderVariants as $variant) {
            $quantity = random_int(1, 3);
            $lineTotal = $variant->price_amount * $quantity;
            $subtotal += $lineTotal;

            $order->lines()->create([
                'store_id' => $store->id,
                'product_id' => $variant->product->id,
                'variant_id' => $variant->id,
                'title_snapshot' => $variant->product->title,
                'sku_snapshot' => $variant->sku,
                'quantity' => $quantity,
                'unit_price_amount' => $variant->price_amount,
                'total_amount' => $lineTotal,
                'tax_lines_json' => [],
                'discount_allocations_json' => [],
            ]);
        }

        $tax = intdiv($subtotal * 800, 10000);
        $order->update([
            'subtotal_amount' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + 599 + $tax,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'provider' => 'mock',
            'method' => $method->value,
            'provider_payment_id' => 'mock_'.Str::lower(Str::random(24)),
            'status' => $paymentStatus->value,
            'amount' => $order->total_amount,
            'currency' => $store->default_currency,
            'raw_json_encrypted' => ['outcome' => $paymentStatus->value],
        ]);

        return $order->load('lines');
    }
}
