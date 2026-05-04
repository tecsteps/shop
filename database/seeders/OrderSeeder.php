<?php

namespace Database\Seeders;

use App\Enums\FinancialStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->orders() as $storeHandle => $orders) {
                $store = Store::query()->where('handle', $storeHandle)->firstOrFail();

                $this->resetSeededOrders($store, array_column($orders, 'order_number'));

                foreach ($orders as $orderData) {
                    $this->createOrder($store, $orderData);
                }
            }

            $this->reservePendingInventory();
        });
    }

    /**
     * @param  list<string>  $orderNumbers
     */
    private function resetSeededOrders(Store $store, array $orderNumbers): void
    {
        Order::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->whereIn('order_number', $orderNumbers)
            ->get()
            ->each(function (Order $order): void {
                $order->refunds()->delete();
                $order->payments()->delete();

                $order->fulfillments()
                    ->get()
                    ->each(function (Fulfillment $fulfillment): void {
                        $fulfillment->lines()->delete();
                        $fulfillment->delete();
                    });

                $order->lines()->delete();
            });
    }

    /**
     * @param  array<string, mixed>  $orderData
     */
    private function createOrder(Store $store, array $orderData): void
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('email', $orderData['customer_email'])
            ->firstOrFail();
        $address = $this->defaultAddress($customer);

        $order = Order::withoutGlobalScopes()->updateOrCreate(
            [
                'store_id' => $store->getKey(),
                'order_number' => $orderData['order_number'],
            ],
            [
                'customer_id' => $customer->getKey(),
                'payment_method' => $orderData['payment_method'],
                'status' => $orderData['status'],
                'financial_status' => $orderData['financial_status'],
                'fulfillment_status' => $orderData['fulfillment_status'],
                'currency' => $store->default_currency,
                'subtotal_amount' => $orderData['subtotal_amount'],
                'discount_amount' => $orderData['discount_amount'],
                'shipping_amount' => $orderData['shipping_amount'],
                'tax_amount' => $orderData['tax_amount'],
                'total_amount' => $orderData['total_amount'],
                'email' => $customer->email,
                'billing_address_json' => $address,
                'shipping_address_json' => $address,
                'placed_at' => $orderData['placed_at'],
            ],
        );

        foreach ($orderData['lines'] as $lineData) {
            $this->createOrderLine($store, $order, $lineData);
        }
    }

    /**
     * @param  array<string, mixed>  $lineData
     */
    private function createOrderLine(Store $store, Order $order, array $lineData): void
    {
        $product = Product::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('handle', $lineData['product'])
            ->firstOrFail();
        $variant = $this->variant($product, $lineData['options'] ?? []);
        $discountAmount = (int) ($lineData['discount_amount'] ?? 0);

        $order->lines()->create([
            'product_id' => $product->getKey(),
            'variant_id' => $variant->getKey(),
            'title_snapshot' => $this->titleSnapshot($product, $lineData['options'] ?? []),
            'sku_snapshot' => $variant->sku,
            'quantity' => $lineData['quantity'],
            'unit_price_amount' => $lineData['unit_price_amount'],
            'total_amount' => $lineData['total_amount'],
            'tax_lines_json' => [],
            'discount_allocations_json' => $discountAmount > 0 ? [[
                'discount_id' => $this->welcomeDiscount($store)->getKey(),
                'code' => 'WELCOME10',
                'amount' => $discountAmount,
            ]] : [],
        ]);
    }

    /**
     * @param  array<string, string>  $options
     */
    private function variant(Product $product, array $options): ProductVariant
    {
        $query = ProductVariant::withoutGlobalScopes()
            ->where('product_id', $product->getKey());

        foreach ($options as $optionName => $value) {
            $query->whereHas('optionValues', function ($query) use ($optionName, $value): void {
                $query
                    ->withoutGlobalScopes()
                    ->where('value', $value)
                    ->whereHas('option', fn ($query) => $query
                        ->withoutGlobalScopes()
                        ->where('name', $optionName));
            });
        }

        return $query->oldest('position')->firstOrFail();
    }

    /**
     * @param  array<string, string>  $options
     */
    private function titleSnapshot(Product $product, array $options): string
    {
        if ($options === []) {
            return $product->title;
        }

        return $product->title.' - '.implode(' / ', array_values($options));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultAddress(Customer $customer): array
    {
        $address = CustomerAddress::query()
            ->where('customer_id', $customer->getKey())
            ->orderByDesc('is_default')
            ->oldest('id')
            ->firstOrFail();

        return $address->address_json ?? [];
    }

    private function welcomeDiscount(Store $store): Discount
    {
        return Discount::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->where('code', 'WELCOME10')
            ->firstOrFail();
    }

    private function reservePendingInventory(): void
    {
        $reservations = Order::withoutGlobalScopes()
            ->with('lines')
            ->where('financial_status', FinancialStatus::Pending->value)
            ->whereIn('order_number', ['#1005', '#5003'])
            ->get()
            ->flatMap(fn (Order $order) => $order->lines)
            ->filter(fn ($line): bool => $line->variant_id !== null)
            ->groupBy('variant_id')
            ->map(fn ($lines): int => (int) $lines->sum('quantity'));

        $reservations->each(function (int $quantity, int $variantId): void {
            InventoryItem::withoutGlobalScopes()
                ->where('variant_id', $variantId)
                ->update(['quantity_reserved' => $quantity]);
        });
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function orders(): array
    {
        return [
            'acme-fashion' => [
                $this->order('#1001', 'customer@acme.test', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, 4998, 0, 499, 798, 5497, now()->subDays(2), [
                    $this->line('classic-cotton-t-shirt', ['Size' => 'S', 'Color' => 'White'], 2, 2499, 4998),
                ]),
                $this->order('#1002', 'customer@acme.test', PaymentMethod::CreditCard, OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, 8498, 0, 499, 1357, 8997, now()->subDays(10), [
                    $this->line('organic-hoodie', ['Size' => 'M'], 1, 5999, 5999),
                    $this->line('classic-cotton-t-shirt', ['Size' => 'L', 'Color' => 'Black'], 1, 2499, 2499),
                ]),
                $this->order('#1003', 'jane@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Partial, 11498, 0, 499, 1836, 11997, now()->subDays(5), [
                    $this->line('premium-slim-fit-jeans', ['Size' => '32', 'Color' => 'Blue'], 1, 7999, 7999),
                    $this->line('leather-belt', ['Size' => 'L/XL', 'Color' => 'Brown'], 1, 3499, 3499),
                ]),
                $this->order('#1004', 'customer@acme.test', PaymentMethod::CreditCard, OrderStatus::Cancelled, FinancialStatus::Refunded, FulfillmentStatus::Unfulfilled, 2499, 0, 499, 399, 2998, now()->subDays(15), [
                    $this->line('classic-cotton-t-shirt', ['Size' => 'M', 'Color' => 'Navy'], 1, 2499, 2499),
                ]),
                $this->order('#1005', 'jane@example.com', PaymentMethod::BankTransfer, OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled, 3499, 0, 499, 559, 3998, now()->subHours(2), [
                    $this->line('leather-belt', ['Size' => 'S/M', 'Color' => 'Black'], 1, 3499, 3499),
                ]),
                $this->order('#1006', 'michael@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, 11999, 0, 499, 1916, 12498, now()->subDay(), [
                    $this->line('running-sneakers', ['Size' => 'EU 42', 'Color' => 'Black'], 1, 11999, 11999),
                ]),
                $this->order('#1007', 'sarah@example.com', PaymentMethod::Paypal, OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, 9997, 0, 499, 1596, 10496, now()->subDays(20), [
                    $this->line('v-neck-linen-tee', ['Size' => 'M', 'Color' => 'Beige'], 2, 3499, 6998),
                    $this->line('wool-scarf', ['Color' => 'Grey'], 1, 2999, 2999),
                ]),
                $this->order('#1008', 'david@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::PartiallyRefunded, FulfillmentStatus::Fulfilled, 8498, 0, 499, 1357, 8997, now()->subDays(12), [
                    $this->line('cargo-pants', ['Size' => '32', 'Color' => 'Khaki'], 1, 5499, 5499),
                    $this->line('graphic-print-tee', ['Size' => 'L'], 1, 2999, 2999),
                ]),
                $this->order('#1009', 'emma@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, 4498, 0, 499, 718, 4997, now()->subDays(3), [
                    $this->line('canvas-tote-bag', ['Color' => 'Natural'], 1, 1999, 1999),
                    $this->line('bucket-hat', ['Size' => 'S/M', 'Color' => 'Black'], 1, 2499, 2499),
                ]),
                $this->order('#1010', 'customer@acme.test', PaymentMethod::Paypal, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, 49999, 0, 499, 7983, 50498, now()->subDay(), [
                    $this->line('cashmere-overcoat', ['Size' => 'M', 'Color' => 'Camel'], 1, 49999, 49999),
                ]),
                $this->order('#1011', 'james@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, 2799, 0, 499, 447, 3298, now()->subDays(25), [
                    $this->line('striped-polo-shirt', ['Size' => 'XL'], 1, 2799, 2799),
                ]),
                $this->order('#1012', 'lisa@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, 7998, 0, 499, 1277, 8497, now()->subDays(4), [
                    $this->line('chino-shorts', ['Size' => '34', 'Color' => 'Navy'], 2, 3999, 7998),
                ]),
                $this->order('#1013', 'robert@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, 7998, 0, 499, 1277, 8497, now()->subDay(), [
                    $this->line('wide-leg-trousers', ['Size' => 'M'], 1, 4999, 4999),
                    $this->line('wool-scarf', ['Color' => 'Burgundy'], 1, 2999, 2999),
                ]),
                $this->order('#1014', 'anna@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, 5000, 0, 0, 798, 5000, now()->subDays(14), [
                    $this->line('gift-card', ['Amount' => '50 EUR'], 1, 5000, 5000),
                ]),
                $this->order('#1015', 'customer@acme.test', PaymentMethod::BankTransfer, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, 5498, 550, 499, 790, 5447, now(), [
                    $this->line('classic-cotton-t-shirt', ['Size' => 'M', 'Color' => 'White'], 1, 2499, 2499, 250),
                    $this->line('graphic-print-tee', ['Size' => 'M'], 1, 2999, 2999, 300),
                ]),
            ],
            'acme-electronics' => [
                $this->order('#5001', 'techfan@example.com', PaymentMethod::CreditCard, OrderStatus::Fulfilled, FinancialStatus::Paid, FulfillmentStatus::Fulfilled, 121298, 0, 0, 0, 121298, now()->subDays(6), [
                    $this->line('pro-laptop-15', ['Storage' => '512GB'], 1, 119999, 119999),
                    $this->line('usb-c-cable-2m', [], 1, 1299, 1299),
                ]),
                $this->order('#5002', 'gadgetlover@example.com', PaymentMethod::CreditCard, OrderStatus::Paid, FinancialStatus::Paid, FulfillmentStatus::Unfulfilled, 14999, 0, 0, 0, 14999, now()->subDays(2), [
                    $this->line('wireless-headphones', ['Color' => 'Black'], 1, 14999, 14999),
                ]),
                $this->order('#5003', 'techfan@example.com', PaymentMethod::BankTransfer, OrderStatus::Pending, FinancialStatus::Pending, FulfillmentStatus::Unfulfilled, 4999, 0, 0, 0, 4999, now()->subHours(6), [
                    $this->line('monitor-stand', [], 1, 4999, 4999),
                ]),
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function order(
        string $orderNumber,
        string $customerEmail,
        PaymentMethod $paymentMethod,
        OrderStatus $status,
        FinancialStatus $financialStatus,
        FulfillmentStatus $fulfillmentStatus,
        int $subtotalAmount,
        int $discountAmount,
        int $shippingAmount,
        int $taxAmount,
        int $totalAmount,
        mixed $placedAt,
        array $lines,
    ): array {
        return [
            'order_number' => $orderNumber,
            'customer_email' => $customerEmail,
            'payment_method' => $paymentMethod,
            'status' => $status,
            'financial_status' => $financialStatus,
            'fulfillment_status' => $fulfillmentStatus,
            'subtotal_amount' => $subtotalAmount,
            'discount_amount' => $discountAmount,
            'shipping_amount' => $shippingAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'placed_at' => $placedAt,
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     */
    private function line(
        string $product,
        array $options,
        int $quantity,
        int $unitPriceAmount,
        int $totalAmount,
        int $discountAmount = 0,
    ): array {
        return [
            'product' => $product,
            'options' => $options,
            'quantity' => $quantity,
            'unit_price_amount' => $unitPriceAmount,
            'total_amount' => $totalAmount,
            'discount_amount' => $discountAmount,
        ];
    }
}
