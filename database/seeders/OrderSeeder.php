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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $fashion = Store::query()->where('handle', 'acme-fashion')->sole();
            foreach ($this->fashionOrders() as $definition) {
                $this->seedOrder($fashion, $definition);
            }

            $electronics = Store::query()->where('handle', 'acme-electronics')->sole();
            foreach ($this->electronicsOrders() as $definition) {
                $this->seedOrder($electronics, $definition);
            }
        });
    }

    /** @param array<string, mixed> $definition */
    private function seedOrder(Store $store, array $definition): void
    {
        $customer = Customer::withoutGlobalScopes()->where('store_id', $store->id)->where('email', $definition['email'])->sole();
        $address = CustomerAddress::query()->where('customer_id', $customer->id)->where('is_default', true)->valueOrFail('address_json');
        $order = Order::withoutGlobalScopes()->updateOrCreate(
            ['store_id' => $store->id, 'order_number' => $definition['number']],
            [
                'customer_id' => $customer->id,
                'payment_method' => $definition['method'],
                'status' => $definition['status'],
                'financial_status' => $definition['financial'],
                'fulfillment_status' => $definition['fulfillment'],
                'currency' => 'EUR',
                'subtotal_amount' => $definition['subtotal'],
                'discount_amount' => $definition['discount'] ?? 0,
                'shipping_amount' => $definition['shipping'],
                'tax_amount' => $definition['tax'],
                'total_amount' => $definition['total'],
                'email' => $customer->email,
                'billing_address_json' => $address,
                'shipping_address_json' => $address,
                'placed_at' => $definition['placed_at'],
            ],
        );

        $lines = [];
        foreach ($definition['lines'] as $lineDefinition) {
            $product = Product::withoutGlobalScopes()->where('store_id', $store->id)->where('handle', $lineDefinition[0])->sole();
            $variant = $this->variant($product, $lineDefinition[1]);
            $allocations = [];
            if (isset($lineDefinition[4])) {
                $discount = Discount::withoutGlobalScopes()->where('store_id', $store->id)->where('code', 'WELCOME10')->sole();
                $allocations[] = ['discount_id' => $discount->id, 'amount' => $lineDefinition[4]];
            }
            $line = OrderLine::query()->updateOrCreate(
                ['order_id' => $order->id, 'product_id' => $product->id, 'variant_id' => $variant->id],
                [
                    'title_snapshot' => $product->title,
                    'sku_snapshot' => $variant->sku,
                    'quantity' => $lineDefinition[2],
                    'unit_price_amount' => $lineDefinition[3],
                    'total_amount' => $lineDefinition[2] * $lineDefinition[3],
                    'tax_lines_json' => [['title' => 'VAT', 'rate_bps' => 1900]],
                    'discount_allocations_json' => $allocations,
                ],
            );

            if ($definition['method'] === 'bank_transfer' && $definition['financial'] === 'pending') {
                $variant->inventoryItem()->update(['quantity_reserved' => $lineDefinition[2]]);
            }

            $lines[] = $line;
        }

        $payment = Payment::query()->updateOrCreate(
            ['order_id' => $order->id, 'provider_payment_id' => 'mock_test_order'.ltrim($definition['number'], '#')],
            ['provider' => 'mock', 'method' => $definition['method'], 'status' => $definition['payment_status'], 'amount' => $definition['total'], 'currency' => 'EUR', 'raw_json_encrypted' => ['seeded' => true]],
        );

        if (isset($definition['refund'])) {
            Refund::query()->updateOrCreate(
                ['order_id' => $order->id, 'provider_refund_id' => 'mock_re_test_order'.ltrim($definition['number'], '#')],
                ['payment_id' => $payment->id, 'amount' => $definition['refund'][0], 'reason' => $definition['refund'][1], 'status' => 'processed'],
            );
        }

        if (isset($definition['shipment'])) {
            [$shipmentStatus, $company, $tracking, $shippedAt, $deliveredAt, $fulfilledLineIndexes] = $definition['shipment'];
            $fulfillment = Fulfillment::query()->updateOrCreate(
                ['order_id' => $order->id, 'tracking_number' => $tracking],
                ['status' => $shipmentStatus, 'tracking_company' => $company, 'tracking_url' => $tracking ? 'https://tracking.example/'.$tracking : null, 'shipped_at' => $shippedAt, 'delivered_at' => $deliveredAt],
            );
            foreach ($fulfilledLineIndexes as $lineIndex) {
                FulfillmentLine::query()->updateOrCreate(
                    ['fulfillment_id' => $fulfillment->id, 'order_line_id' => $lines[$lineIndex]->id],
                    ['quantity' => $lines[$lineIndex]->quantity],
                );
            }
        }
    }

    /** @param list<string> $values */
    private function variant(Product $product, array $values): ProductVariant
    {
        if ($values === []) {
            return $product->variants()->where('is_default', true)->sole();
        }

        return $product->variants()
            ->whereHas('optionValues', fn (Builder $query): Builder => $query->whereIn('value', $values), '=', count($values))
            ->sole();
    }

    /** @return list<array<string, mixed>> */
    private function fashionOrders(): array
    {
        return [
            $this->order('#1001', 'customer@acme.test', 'credit_card', 'paid', 'paid', 'unfulfilled', 4998, 499, 798, 5497, now()->subDays(2), [['classic-cotton-t-shirt', ['S', 'White'], 2, 2499]]),
            $this->order('#1002', 'customer@acme.test', 'credit_card', 'fulfilled', 'paid', 'fulfilled', 8498, 499, 1357, 8997, now()->subDays(10), [['organic-hoodie', ['M'], 1, 5999], ['classic-cotton-t-shirt', ['L', 'Black'], 1, 2499]], ['shipment' => ['delivered', 'DHL', 'DHL1234567890', now()->subDays(8), now()->subDays(6), [0, 1]]]),
            $this->order('#1003', 'jane@example.com', 'credit_card', 'paid', 'paid', 'partial', 11498, 499, 1836, 11997, now()->subDays(5), [['premium-slim-fit-jeans', ['32', 'Blue'], 1, 7999], ['leather-belt', ['L/XL', 'Brown'], 1, 3499]], ['shipment' => ['shipped', 'DHL', 'DHL9876543210', now()->subDays(3), null, [0]]]),
            $this->order('#1004', 'customer@acme.test', 'credit_card', 'cancelled', 'refunded', 'unfulfilled', 2499, 499, 399, 2998, now()->subDays(15), [['classic-cotton-t-shirt', ['M', 'Navy'], 1, 2499]], ['payment_status' => 'refunded', 'refund' => [2998, 'Customer requested cancellation']]),
            $this->order('#1005', 'jane@example.com', 'bank_transfer', 'pending', 'pending', 'unfulfilled', 3499, 499, 559, 3998, now()->subHours(2), [['leather-belt', ['S/M', 'Black'], 1, 3499]], ['payment_status' => 'pending']),
            $this->order('#1006', 'michael@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 11999, 499, 1916, 12498, now()->subDay(), [['running-sneakers', ['EU 42', 'Black'], 1, 11999]]),
            $this->order('#1007', 'sarah@example.com', 'paypal', 'fulfilled', 'paid', 'fulfilled', 9997, 499, 1596, 10496, now()->subDays(20), [['v-neck-linen-tee', ['M', 'Beige'], 2, 3499], ['wool-scarf', ['Grey'], 1, 2999]], ['shipment' => ['delivered', 'DHL', 'DHL1112223334', now()->subDays(18), now()->subDays(16), [0, 1]]]),
            $this->order('#1008', 'david@example.com', 'credit_card', 'paid', 'partially_refunded', 'fulfilled', 8498, 499, 1357, 8997, now()->subDays(12), [['cargo-pants', ['32', 'Khaki'], 1, 5499], ['graphic-print-tee', ['L'], 1, 2999]], ['refund' => [2999, 'Item returned'], 'shipment' => ['delivered', 'UPS', 'UPS5556667778', now()->subDays(10), now()->subDays(8), [0, 1]]]),
            $this->order('#1009', 'emma@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 4498, 499, 718, 4997, now()->subDays(3), [['canvas-tote-bag', ['Natural'], 1, 1999], ['bucket-hat', ['S/M', 'Black'], 1, 2499]]),
            $this->order('#1010', 'customer@acme.test', 'paypal', 'paid', 'paid', 'unfulfilled', 49999, 499, 7983, 50498, now()->subDay(), [['cashmere-overcoat', ['M', 'Camel'], 1, 49999]]),
            $this->order('#1011', 'james@example.com', 'credit_card', 'paid', 'paid', 'fulfilled', 2799, 499, 447, 3298, now()->subDays(25), [['striped-polo-shirt', ['XL'], 1, 2799]], ['shipment' => ['delivered', 'FedEx', 'FX9998887776', now()->subDays(23), now()->subDays(21), [0]]]),
            $this->order('#1012', 'lisa@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 7998, 499, 1277, 8497, now()->subDays(4), [['chino-shorts', ['34', 'Navy'], 2, 3999]]),
            $this->order('#1013', 'robert@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 7998, 499, 1277, 8497, now()->subDay(), [['wide-leg-trousers', ['M'], 1, 4999], ['wool-scarf', ['Burgundy'], 1, 2999]]),
            $this->order('#1014', 'anna@example.com', 'credit_card', 'paid', 'paid', 'fulfilled', 5000, 0, 798, 5000, now()->subDays(14), [['gift-card', ['50 EUR'], 1, 5000]], ['shipment' => ['delivered', null, null, now()->subDays(14), now()->subDays(14), [0]]]),
            $this->order('#1015', 'customer@acme.test', 'bank_transfer', 'paid', 'paid', 'unfulfilled', 5498, 499, 790, 5447, now(), [['classic-cotton-t-shirt', ['M', 'White'], 1, 2499, 250], ['graphic-print-tee', ['M'], 1, 2999, 300]], ['discount' => 550]),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function electronicsOrders(): array
    {
        return [
            $this->order('#5001', 'techfan@example.com', 'credit_card', 'fulfilled', 'paid', 'fulfilled', 121298, 0, 19368, 121298, now()->subDays(6), [['pro-laptop-15', ['512GB'], 1, 119999], ['usb-c-cable-2m', [], 1, 1299]], ['shipment' => ['delivered', 'DHL', 'DHL5001000001', now()->subDays(5), now()->subDays(3), [0, 1]]]),
            $this->order('#5002', 'gadgetlover@example.com', 'credit_card', 'paid', 'paid', 'unfulfilled', 14999, 0, 2395, 14999, now()->subDay(), [['wireless-headphones', ['Black'], 1, 14999]]),
            $this->order('#5003', 'techfan@example.com', 'bank_transfer', 'pending', 'pending', 'unfulfilled', 4999, 0, 798, 4999, now()->subHours(3), [['monitor-stand', [], 1, 4999]], ['payment_status' => 'pending']),
        ];
    }

    /** @param list<array<int, mixed>> $lines
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function order(string $number, string $email, string $method, string $status, string $financial, string $fulfillment, int $subtotal, int $shipping, int $tax, int $total, mixed $placedAt, array $lines, array $extra = []): array
    {
        return [...compact('number', 'email', 'method', 'status', 'financial', 'fulfillment', 'subtotal', 'shipping', 'tax', 'total', 'lines'), 'placed_at' => $placedAt, 'payment_status' => 'captured', 'discount' => 0, ...$extra];
    }
}
