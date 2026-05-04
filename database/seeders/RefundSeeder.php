<?php

namespace Database\Seeders;

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Refund;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RefundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->refunds() as $storeHandle => $refunds) {
                $store = Store::query()->where('handle', $storeHandle)->firstOrFail();
                $orders = Order::withoutGlobalScopes()
                    ->with('payments')
                    ->where('store_id', $store->getKey())
                    ->whereIn('order_number', array_keys($refunds))
                    ->get()
                    ->keyBy('order_number');

                Refund::query()
                    ->whereIn('order_id', $orders->pluck('id'))
                    ->delete();

                foreach ($refunds as $orderNumber => $refundData) {
                    $order = $orders->get($orderNumber);

                    if (! $order instanceof Order) {
                        continue;
                    }

                    $payment = $order->payments->first();

                    if ($payment === null) {
                        continue;
                    }

                    Refund::query()->create([
                        'order_id' => $order->getKey(),
                        'payment_id' => $payment->getKey(),
                        'amount' => $refundData['amount'],
                        'reason' => $refundData['reason'],
                        'status' => RefundStatus::Processed,
                        'provider_refund_id' => $refundData['provider_refund_id'],
                    ]);
                }
            }
        });
    }

    /**
     * @return array<string, array<string, array{amount: int, reason: string, provider_refund_id: string}>>
     */
    private function refunds(): array
    {
        return [
            'acme-fashion' => [
                '#1004' => [
                    'amount' => 2998,
                    'reason' => 'Customer requested cancellation',
                    'provider_refund_id' => 'mock_re_test_order1004',
                ],
                '#1008' => [
                    'amount' => 2999,
                    'reason' => 'Item returned',
                    'provider_refund_id' => 'mock_re_test_order1008',
                ],
            ],
        ];
    }
}
