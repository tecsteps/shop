<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\Fulfillment;
use App\Models\FulfillmentLine;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\Store;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    use SeedsDemoData;

    /**
     * Seed orders with lines, payments, fulfillments and refunds for every store.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedStore('acme-fashion', $this->fashionOrders());
            $this->seedStore('acme-electronics', $this->electronicsOrders());
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $orders
     */
    private function seedStore(string $storeHandle, array $orders): void
    {
        $store = Store::where('handle', $storeHandle)->firstOrFail();

        $variants = ProductVariant::with('product')
            ->whereHas('product', fn ($query) => $query->where('store_id', $store->id))
            ->get()
            ->keyBy('sku');

        $welcome10Id = Discount::where('store_id', $store->id)->where('code', 'WELCOME10')->value('id');

        foreach ($orders as $data) {
            $customer = Customer::where('store_id', $store->id)->where('email', $data['customer'])->firstOrFail();
            $address = $this->addressForCustomer($customer);

            $order = Order::updateOrCreate(
                ['store_id' => $store->id, 'order_number' => $data['number']],
                [
                    'customer_id' => $customer->id,
                    'payment_method' => $data['method'],
                    'status' => $data['status'],
                    'financial_status' => $data['financial'],
                    'fulfillment_status' => $data['fulfillment'],
                    'currency' => 'EUR',
                    'subtotal_amount' => $data['totals']['subtotal'],
                    'discount_amount' => $data['totals']['discount'],
                    'shipping_amount' => $data['totals']['shipping'],
                    'tax_amount' => $data['totals']['tax'],
                    'total_amount' => $data['totals']['total'],
                    'email' => $customer->email,
                    'billing_address_json' => $address,
                    'shipping_address_json' => $address,
                    'placed_at' => $this->resolveDate($data['placed_at']),
                ],
            );

            // Rebuild child records deterministically so re-runs stay clean.
            $order->refunds()->delete();
            $order->payments()->delete();
            $order->fulfillments()->delete();
            $order->lines()->delete();

            $lineIds = [];

            foreach ($data['lines'] as $line) {
                $variant = $variants[$line['sku']];

                $orderLine = OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'title_snapshot' => $variant->product->title,
                    'sku_snapshot' => $variant->sku,
                    'quantity' => $line['qty'],
                    'unit_price_amount' => $variant->price_amount,
                    'total_amount' => $variant->price_amount * $line['qty'],
                    'tax_lines_json' => [],
                    'discount_allocations_json' => isset($line['discount']) && $welcome10Id !== null
                        ? [['discount_id' => $welcome10Id, 'amount' => $line['discount']]]
                        : [],
                ]);

                $lineIds[] = $orderLine->id;
            }

            Payment::create([
                'order_id' => $order->id,
                'provider' => 'mock',
                'method' => $data['method'],
                'provider_payment_id' => $data['payment']['id'],
                'status' => $data['payment']['status'],
                'amount' => $data['totals']['total'],
                'currency' => 'EUR',
                'raw_json_encrypted' => null,
            ]);

            foreach ($data['fulfillments'] as $fulfillmentData) {
                $fulfillment = Fulfillment::create([
                    'order_id' => $order->id,
                    'status' => $fulfillmentData['status'],
                    'tracking_company' => $fulfillmentData['company'] ?? null,
                    'tracking_number' => $fulfillmentData['number'] ?? null,
                    'tracking_url' => isset($fulfillmentData['number'])
                        ? 'https://tracking.example.com/'.$fulfillmentData['number']
                        : null,
                    'shipped_at' => $this->resolveDate($fulfillmentData['shipped_at']),
                    'delivered_at' => $this->resolveDate($fulfillmentData['delivered_at'] ?? null),
                ]);

                foreach ($fulfillmentData['lines'] as $lineIndex) {
                    FulfillmentLine::create([
                        'fulfillment_id' => $fulfillment->id,
                        'order_line_id' => $lineIds[$lineIndex],
                        'quantity' => $data['lines'][$lineIndex]['qty'],
                    ]);
                }
            }

            foreach ($data['refunds'] as $refundData) {
                Refund::create([
                    'order_id' => $order->id,
                    'payment_id' => $order->payments()->firstOrFail()->id,
                    'amount' => $refundData['amount'],
                    'reason' => $refundData['reason'],
                    'status' => $refundData['status'],
                    'provider_refund_id' => $refundData['provider_refund_id'] ?? null,
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function addressForCustomer(Customer $customer): array
    {
        $address = DB::table('customer_addresses')
            ->where('customer_id', $customer->id)
            ->where('is_default', true)
            ->first();

        if ($address === null) {
            return $this->demoAddress();
        }

        $decoded = json_decode((string) $address->address_json, true) ?: [];

        return $this->demoAddress($decoded);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fashionOrders(): array
    {
        return [
            [
                'number' => '#1001',
                'customer' => 'customer@acme.test',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-2 days',
                'lines' => [
                    ['sku' => 'ACME-CTSH-S-WHT', 'qty' => 2],
                ],
                'totals' => ['subtotal' => 4998, 'discount' => 0, 'shipping' => 499, 'tax' => 798, 'total' => 5497],
                'payment' => ['id' => 'mock_test_order1001', 'status' => 'captured'],
                'fulfillments' => [],
                'refunds' => [],
            ],
            [
                'number' => '#1002',
                'customer' => 'customer@acme.test',
                'method' => 'credit_card',
                'status' => 'fulfilled',
                'financial' => 'paid',
                'fulfillment' => 'fulfilled',
                'placed_at' => '-10 days',
                'lines' => [
                    ['sku' => 'ACME-HOOD-M', 'qty' => 1],
                    ['sku' => 'ACME-CTSH-L-BLK', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997],
                'payment' => ['id' => 'mock_test_order1002', 'status' => 'captured'],
                'fulfillments' => [
                    [
                        'status' => 'delivered',
                        'company' => 'DHL',
                        'number' => 'DHL1234567890',
                        'shipped_at' => '-8 days',
                        'delivered_at' => '-7 days',
                        'lines' => [0, 1],
                    ],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1003',
                'customer' => 'jane@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'partial',
                'placed_at' => '-5 days',
                'lines' => [
                    ['sku' => 'ACME-JEAN-32-BLU', 'qty' => 1],
                    ['sku' => 'ACME-BELT-LX-BRN', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 11498, 'discount' => 0, 'shipping' => 499, 'tax' => 1836, 'total' => 11997],
                'payment' => ['id' => 'mock_test_order1003', 'status' => 'captured'],
                'fulfillments' => [
                    [
                        'status' => 'shipped',
                        'company' => 'DHL',
                        'number' => 'DHL9876543210',
                        'shipped_at' => '-3 days',
                        'lines' => [0],
                    ],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1004',
                'customer' => 'customer@acme.test',
                'method' => 'credit_card',
                'status' => 'cancelled',
                'financial' => 'refunded',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-15 days',
                'lines' => [
                    ['sku' => 'ACME-CTSH-M-NVY', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 2499, 'discount' => 0, 'shipping' => 499, 'tax' => 399, 'total' => 2998],
                'payment' => ['id' => 'mock_test_order1004', 'status' => 'refunded'],
                'fulfillments' => [],
                'refunds' => [
                    [
                        'amount' => 2998,
                        'reason' => 'Customer requested cancellation',
                        'status' => 'processed',
                        'provider_refund_id' => 'mock_re_test_order1004',
                    ],
                ],
            ],
            [
                'number' => '#1005',
                'customer' => 'jane@example.com',
                'method' => 'bank_transfer',
                'status' => 'pending',
                'financial' => 'pending',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-2 hours',
                'lines' => [
                    ['sku' => 'ACME-BELT-SM-BLK', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 3499, 'discount' => 0, 'shipping' => 499, 'tax' => 559, 'total' => 3998],
                'payment' => ['id' => 'mock_test_order1005', 'status' => 'pending'],
                'fulfillments' => [],
                'refunds' => [],
            ],
            [
                'number' => '#1006',
                'customer' => 'michael@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-1 day',
                'lines' => [
                    ['sku' => 'ACME-SNKR-EU42-BLK', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 11999, 'discount' => 0, 'shipping' => 499, 'tax' => 1916, 'total' => 12498],
                'payment' => ['id' => 'mock_test_order1006', 'status' => 'captured'],
                'fulfillments' => [],
                'refunds' => [],
            ],
            [
                'number' => '#1007',
                'customer' => 'sarah@example.com',
                'method' => 'paypal',
                'status' => 'fulfilled',
                'financial' => 'paid',
                'fulfillment' => 'fulfilled',
                'placed_at' => '-20 days',
                'lines' => [
                    ['sku' => 'ACME-LNTE-M-BGE', 'qty' => 2],
                    ['sku' => 'ACME-SCARF-GRY', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 9997, 'discount' => 0, 'shipping' => 499, 'tax' => 1596, 'total' => 10496],
                'payment' => ['id' => 'mock_test_order1007', 'status' => 'captured'],
                'fulfillments' => [
                    [
                        'status' => 'delivered',
                        'company' => 'DHL',
                        'number' => 'DHL1112223334',
                        'shipped_at' => '-18 days',
                        'delivered_at' => '-16 days',
                        'lines' => [0, 1],
                    ],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1008',
                'customer' => 'david@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'partially_refunded',
                'fulfillment' => 'fulfilled',
                'placed_at' => '-12 days',
                'lines' => [
                    ['sku' => 'ACME-CARGO-32-KHK', 'qty' => 1],
                    ['sku' => 'ACME-GPT-L', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 8498, 'discount' => 0, 'shipping' => 499, 'tax' => 1357, 'total' => 8997],
                'payment' => ['id' => 'mock_test_order1008', 'status' => 'captured'],
                'fulfillments' => [
                    [
                        'status' => 'delivered',
                        'company' => 'UPS',
                        'number' => 'UPS5556667778',
                        'shipped_at' => '-10 days',
                        'delivered_at' => '-8 days',
                        'lines' => [0, 1],
                    ],
                ],
                'refunds' => [
                    [
                        'amount' => 2999,
                        'reason' => 'Item returned',
                        'status' => 'processed',
                        'provider_refund_id' => 'mock_re_test_order1008',
                    ],
                ],
            ],
            [
                'number' => '#1009',
                'customer' => 'emma@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-3 days',
                'lines' => [
                    ['sku' => 'ACME-TOTE-NAT', 'qty' => 1],
                    ['sku' => 'ACME-BHAT-SM-BLK', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 4498, 'discount' => 0, 'shipping' => 499, 'tax' => 718, 'total' => 4997],
                'payment' => ['id' => 'mock_test_order1009', 'status' => 'captured'],
                'fulfillments' => [],
                'refunds' => [],
            ],
            [
                'number' => '#1010',
                'customer' => 'customer@acme.test',
                'method' => 'paypal',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-1 day',
                'lines' => [
                    ['sku' => 'ACME-OVER-M-CML', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 49999, 'discount' => 0, 'shipping' => 499, 'tax' => 7983, 'total' => 50498],
                'payment' => ['id' => 'mock_test_order1010', 'status' => 'captured'],
                'fulfillments' => [],
                'refunds' => [],
            ],
            [
                'number' => '#1011',
                'customer' => 'james@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'fulfilled',
                'placed_at' => '-25 days',
                'lines' => [
                    ['sku' => 'ACME-POLO-XL', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 2799, 'discount' => 0, 'shipping' => 499, 'tax' => 447, 'total' => 3298],
                'payment' => ['id' => 'mock_test_order1011', 'status' => 'captured'],
                'fulfillments' => [
                    [
                        'status' => 'delivered',
                        'company' => 'FedEx',
                        'number' => 'FX9998887776',
                        'shipped_at' => '-23 days',
                        'delivered_at' => '-21 days',
                        'lines' => [0],
                    ],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1012',
                'customer' => 'lisa@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-4 days',
                'lines' => [
                    ['sku' => 'ACME-CHINO-34-NVY', 'qty' => 2],
                ],
                'totals' => ['subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497],
                'payment' => ['id' => 'mock_test_order1012', 'status' => 'captured'],
                'fulfillments' => [],
                'refunds' => [],
            ],
            [
                'number' => '#1013',
                'customer' => 'robert@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-1 day',
                'lines' => [
                    ['sku' => 'ACME-WLT-M', 'qty' => 1],
                    ['sku' => 'ACME-SCARF-BUR', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 7998, 'discount' => 0, 'shipping' => 499, 'tax' => 1277, 'total' => 8497],
                'payment' => ['id' => 'mock_test_order1013', 'status' => 'captured'],
                'fulfillments' => [],
                'refunds' => [],
            ],
            [
                'number' => '#1014',
                'customer' => 'anna@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'fulfilled',
                'placed_at' => '-14 days',
                'lines' => [
                    ['sku' => 'ACME-GIFT-50', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 5000, 'discount' => 0, 'shipping' => 0, 'tax' => 798, 'total' => 5000],
                'payment' => ['id' => 'mock_test_order1014', 'status' => 'captured'],
                'fulfillments' => [
                    [
                        'status' => 'delivered',
                        'shipped_at' => '-14 days',
                        'delivered_at' => '-14 days',
                        'lines' => [0],
                    ],
                ],
                'refunds' => [],
            ],
            [
                'number' => '#1015',
                'customer' => 'customer@acme.test',
                'method' => 'bank_transfer',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'unfulfilled',
                'placed_at' => 'now',
                'lines' => [
                    ['sku' => 'ACME-CTSH-M-WHT', 'qty' => 1, 'discount' => 250],
                    ['sku' => 'ACME-GPT-M', 'qty' => 1, 'discount' => 300],
                ],
                'totals' => ['subtotal' => 5498, 'discount' => 550, 'shipping' => 499, 'tax' => 790, 'total' => 5447],
                'payment' => ['id' => 'mock_test_order1015', 'status' => 'captured'],
                'fulfillments' => [],
                'refunds' => [],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function electronicsOrders(): array
    {
        return [
            [
                'number' => '#5001',
                'customer' => 'techfan@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'fulfilled',
                'placed_at' => '-7 days',
                'lines' => [
                    ['sku' => 'TECH-LAP-512GB', 'qty' => 1],
                    ['sku' => 'CABLE-USBC-2M', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 121298, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 121298],
                'payment' => ['id' => 'mock_test_order5001', 'status' => 'captured'],
                'fulfillments' => [],
                'refunds' => [],
            ],
            [
                'number' => '#5002',
                'customer' => 'gadgetlover@example.com',
                'method' => 'credit_card',
                'status' => 'paid',
                'financial' => 'paid',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-2 days',
                'lines' => [
                    ['sku' => 'AUDIO-WH-BLK', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 14999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 14999],
                'payment' => ['id' => 'mock_test_order5002', 'status' => 'captured'],
                'fulfillments' => [],
                'refunds' => [],
            ],
            [
                'number' => '#5003',
                'customer' => 'techfan@example.com',
                'method' => 'bank_transfer',
                'status' => 'pending',
                'financial' => 'pending',
                'fulfillment' => 'unfulfilled',
                'placed_at' => '-1 hour',
                'lines' => [
                    ['sku' => 'DESK-STD-1', 'qty' => 1],
                ],
                'totals' => ['subtotal' => 4999, 'discount' => 0, 'shipping' => 0, 'tax' => 0, 'total' => 4999],
                'payment' => ['id' => 'mock_test_order5003', 'status' => 'pending'],
                'fulfillments' => [],
                'refunds' => [],
            ],
        ];
    }
}
