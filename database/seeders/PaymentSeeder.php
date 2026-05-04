<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->payments() as $storeHandle => $payments) {
                $store = Store::query()->where('handle', $storeHandle)->firstOrFail();
                $orders = Order::withoutGlobalScopes()
                    ->where('store_id', $store->getKey())
                    ->whereIn('order_number', array_keys($payments))
                    ->get()
                    ->keyBy('order_number');

                Payment::query()
                    ->whereIn('order_id', $orders->pluck('id'))
                    ->delete();

                foreach ($payments as $orderNumber => $paymentData) {
                    $order = $orders->get($orderNumber);

                    if (! $order instanceof Order) {
                        continue;
                    }

                    Payment::query()->create([
                        'order_id' => $order->getKey(),
                        'provider' => 'mock',
                        'method' => $paymentData['method'],
                        'provider_payment_id' => $paymentData['provider_payment_id'],
                        'status' => $paymentData['status'],
                        'amount' => $paymentData['amount'],
                        'currency' => $order->currency,
                        'raw_json_encrypted' => [
                            'success' => true,
                            'status' => $paymentData['status']->value,
                            'reference_id' => $paymentData['provider_payment_id'],
                        ],
                    ]);
                }
            }
        });
    }

    /**
     * @return array<string, array<string, array{method: PaymentMethod, provider_payment_id: string, status: PaymentStatus, amount: int}>>
     */
    private function payments(): array
    {
        return [
            'acme-fashion' => [
                '#1001' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1001', PaymentStatus::Captured, 5497),
                '#1002' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1002', PaymentStatus::Captured, 8997),
                '#1003' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1003', PaymentStatus::Captured, 11997),
                '#1004' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1004', PaymentStatus::Refunded, 2998),
                '#1005' => $this->payment(PaymentMethod::BankTransfer, 'mock_test_order1005', PaymentStatus::Pending, 3998),
                '#1006' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1006', PaymentStatus::Captured, 12498),
                '#1007' => $this->payment(PaymentMethod::Paypal, 'mock_test_order1007', PaymentStatus::Captured, 10496),
                '#1008' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1008', PaymentStatus::Captured, 8997),
                '#1009' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1009', PaymentStatus::Captured, 4997),
                '#1010' => $this->payment(PaymentMethod::Paypal, 'mock_test_order1010', PaymentStatus::Captured, 50498),
                '#1011' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1011', PaymentStatus::Captured, 3298),
                '#1012' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1012', PaymentStatus::Captured, 8497),
                '#1013' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1013', PaymentStatus::Captured, 8497),
                '#1014' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order1014', PaymentStatus::Captured, 5000),
                '#1015' => $this->payment(PaymentMethod::BankTransfer, 'mock_test_order1015', PaymentStatus::Captured, 5447),
            ],
            'acme-electronics' => [
                '#5001' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order5001', PaymentStatus::Captured, 121298),
                '#5002' => $this->payment(PaymentMethod::CreditCard, 'mock_test_order5002', PaymentStatus::Captured, 14999),
                '#5003' => $this->payment(PaymentMethod::BankTransfer, 'mock_test_order5003', PaymentStatus::Pending, 4999),
            ],
        ];
    }

    /**
     * @return array{method: PaymentMethod, provider_payment_id: string, status: PaymentStatus, amount: int}
     */
    private function payment(PaymentMethod $method, string $providerPaymentId, PaymentStatus $status, int $amount): array
    {
        return [
            'method' => $method,
            'provider_payment_id' => $providerPaymentId,
            'status' => $status,
            'amount' => $amount,
        ];
    }
}
