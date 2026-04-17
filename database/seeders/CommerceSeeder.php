<?php

namespace Database\Seeders;

use App\Enums\CartStatus;
use App\Enums\CheckoutStatus;
use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderFinancialStatus;
use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Enums\ShippingRateType;
use App\Enums\TaxMode;
use App\Enums\TaxProvider;
use App\Models\Cart;
use App\Models\CartLine;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\TaxSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        $store = Store::query()->where('handle', 'demo-store')->firstOrFail();
        $variant = ProductVariant::query()->where('sku', 'DEMO-TSHIRT-M')->firstOrFail();

        $customer = Customer::query()->updateOrCreate(
            ['store_id' => $store->id, 'email' => 'customer@demo-store.test'],
            [
                'name' => 'Demo Customer',
                'password_hash' => Hash::make('password'),
                'marketing_opt_in' => true,
            ],
        );

        CustomerAddress::query()->updateOrCreate(
            ['customer_id' => $customer->id, 'label' => 'Home'],
            [
                'address_json' => [
                    'first_name' => 'Demo',
                    'last_name' => 'Customer',
                    'address1' => 'Main Street 1',
                    'city' => 'Berlin',
                    'country_code' => 'DE',
                    'zip' => '10115',
                ],
                'is_default' => true,
            ],
        );

        $cart = Cart::query()->updateOrCreate(
            ['store_id' => $store->id, 'customer_id' => $customer->id, 'status' => CartStatus::Active],
            [
                'currency' => 'EUR',
                'cart_version' => 1,
            ],
        );

        CartLine::query()->updateOrCreate(
            ['cart_id' => $cart->id, 'variant_id' => $variant->id],
            [
                'quantity' => 2,
                'unit_price_amount' => 2499,
                'line_subtotal_amount' => 4998,
                'line_discount_amount' => 500,
                'line_total_amount' => 4498,
            ],
        );

        $shippingZone = ShippingZone::query()->updateOrCreate(
            ['store_id' => $store->id, 'name' => 'Germany'],
            [
                'countries_json' => ['DE'],
                'regions_json' => [],
            ],
        );

        $shippingRate = ShippingRate::query()->updateOrCreate(
            ['zone_id' => $shippingZone->id, 'name' => 'Standard Shipping'],
            [
                'type' => ShippingRateType::Flat,
                'config_json' => ['amount' => 499],
                'is_active' => true,
            ],
        );

        TaxSetting::query()->updateOrCreate(
            ['store_id' => $store->id],
            [
                'mode' => TaxMode::Manual,
                'provider' => TaxProvider::None,
                'prices_include_tax' => false,
                'config_json' => ['default_rate_bps' => 1900],
            ],
        );

        $discount = Discount::query()->updateOrCreate(
            ['store_id' => $store->id, 'code' => 'WELCOME10'],
            [
                'type' => DiscountType::Code,
                'value_type' => DiscountValueType::Percent,
                'value_amount' => 10,
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonth(),
                'usage_limit' => null,
                'usage_count' => 1,
                'rules_json' => ['min_subtotal_amount' => 2000],
                'status' => DiscountStatus::Active,
            ],
        );

        Checkout::query()->updateOrCreate(
            ['store_id' => $store->id, 'cart_id' => $cart->id],
            [
                'customer_id' => $customer->id,
                'status' => CheckoutStatus::Completed,
                'payment_method' => PaymentMethod::CreditCard,
                'email' => $customer->email,
                'shipping_address_json' => [
                    'address1' => 'Main Street 1',
                    'city' => 'Berlin',
                    'country_code' => 'DE',
                    'zip' => '10115',
                ],
                'billing_address_json' => [
                    'address1' => 'Main Street 1',
                    'city' => 'Berlin',
                    'country_code' => 'DE',
                    'zip' => '10115',
                ],
                'shipping_method_id' => $shippingRate->id,
                'discount_code' => $discount->code,
                'tax_provider_snapshot_json' => ['provider' => 'manual'],
                'totals_json' => [
                    'subtotal' => 4998,
                    'discount' => 500,
                    'shipping' => 499,
                    'tax' => 855,
                    'total' => 5852,
                ],
                'expires_at' => now()->addHours(2),
            ],
        );

        $order = Order::query()->updateOrCreate(
            ['store_id' => $store->id, 'order_number' => '1001'],
            [
                'customer_id' => $customer->id,
                'payment_method' => PaymentMethod::CreditCard,
                'status' => OrderStatus::Paid,
                'financial_status' => OrderFinancialStatus::Paid,
                'fulfillment_status' => OrderFulfillmentStatus::Partial,
                'currency' => 'EUR',
                'subtotal_amount' => 4998,
                'discount_amount' => 500,
                'shipping_amount' => 499,
                'tax_amount' => 855,
                'total_amount' => 5852,
                'email' => $customer->email,
                'billing_address_json' => [
                    'address1' => 'Main Street 1',
                    'city' => 'Berlin',
                    'country_code' => 'DE',
                ],
                'shipping_address_json' => [
                    'address1' => 'Main Street 1',
                    'city' => 'Berlin',
                    'country_code' => 'DE',
                ],
                'placed_at' => now()->subHour(),
            ],
        );

        $orderLine = OrderLine::query()->updateOrCreate(
            ['order_id' => $order->id, 'variant_id' => $variant->id],
            [
                'product_id' => $variant->product_id,
                'title_snapshot' => 'Demo T-Shirt',
                'sku_snapshot' => $variant->sku,
                'quantity' => 2,
                'unit_price_amount' => 2499,
                'total_amount' => 4498,
                'tax_lines_json' => [['title' => 'VAT', 'rate' => 1900, 'amount' => 855]],
                'discount_allocations_json' => [['discount_code' => 'WELCOME10', 'amount' => 500]],
            ],
        );

        $payment = Payment::query()->updateOrCreate(
            ['order_id' => $order->id, 'provider_payment_id' => 'mock_payment_1001'],
            [
                'provider' => PaymentProvider::Mock,
                'method' => PaymentMethod::CreditCard,
                'status' => PaymentStatus::Captured,
                'amount' => 5852,
                'currency' => 'EUR',
                'raw_json_encrypted' => encrypt(json_encode(['result' => 'captured'])),
                'created_at' => now()->subHour(),
            ],
        );

        $fulfillment = Fulfillment::query()->updateOrCreate(
            ['order_id' => $order->id, 'tracking_number' => 'DHL123456789'],
            [
                'status' => FulfillmentStatus::Shipped,
                'tracking_company' => 'DHL',
                'tracking_url' => 'https://tracking.example.test/DHL123456789',
                'shipped_at' => now()->subMinutes(30),
                'created_at' => now()->subMinutes(35),
            ],
        );

        FulfillmentLine::query()->updateOrCreate(
            ['fulfillment_id' => $fulfillment->id, 'order_line_id' => $orderLine->id],
            ['quantity' => 1],
        );
    }
}
