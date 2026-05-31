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
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSettings;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
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
        $this->seedOrders($store);
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

    private function seedOrders(Store $store): void
    {
        if (Order::query()->where('store_id', $store->id)->exists()) {
            return;
        }

        $variants = ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->where('store_id', $store->id))
            ->with('product', 'inventoryItem')
            ->take(6)
            ->get();

        if ($variants->isEmpty()) {
            return; // No catalog seeded yet; nothing to order.
        }

        // A paid, unfulfilled order.
        $this->makeOrder($store, $variants->take(2), PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, PaymentStatus::Captured);

        // A paid + fully fulfilled order.
        $fulfilled = $this->makeOrder($store, $variants->skip(2)->take(2), PaymentMethod::Paypal, OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, PaymentStatus::Captured);
        $shipment = $fulfilled->fulfillments()->create([
            'status' => FulfillmentShipmentStatus::Delivered->value,
            'tracking_company' => 'DHL',
            'tracking_number' => Str::upper(Str::random(10)),
            'shipped_at' => Carbon::now()->subDays(3),
            'delivered_at' => Carbon::now()->subDay(),
        ]);
        foreach ($fulfilled->lines as $line) {
            $shipment->lines()->create(['order_line_id' => $line->id, 'quantity' => $line->quantity]);
        }

        // A pending bank-transfer order.
        $this->makeOrder($store, $variants->skip(4)->take(2), PaymentMethod::BankTransfer, OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled, PaymentStatus::Pending);
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
    ): Order {
        $subtotal = 0;

        $order = Order::create([
            'store_id' => $store->id,
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
            'email' => DemoStoreSeeder::CUSTOMER_EMAIL,
            'placed_at' => Carbon::now()->subDays(random_int(1, 10)),
        ]);

        foreach ($variants as $variant) {
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
