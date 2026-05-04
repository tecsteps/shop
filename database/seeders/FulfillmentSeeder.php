<?php

namespace Database\Seeders;

use App\Enums\FulfillmentShipmentStatus;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FulfillmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->fulfillments() as $storeHandle => $fulfillments) {
                $store = Store::query()->where('handle', $storeHandle)->firstOrFail();
                $orders = Order::withoutGlobalScopes()
                    ->with(['lines.product'])
                    ->where('store_id', $store->getKey())
                    ->whereIn('order_number', array_keys($fulfillments))
                    ->get()
                    ->keyBy('order_number');

                Fulfillment::query()
                    ->whereIn('order_id', $orders->pluck('id'))
                    ->get()
                    ->each(function (Fulfillment $fulfillment): void {
                        $fulfillment->lines()->delete();
                        $fulfillment->delete();
                    });

                foreach ($fulfillments as $orderNumber => $fulfillmentData) {
                    $order = $orders->get($orderNumber);

                    if (! $order instanceof Order) {
                        continue;
                    }

                    $fulfillment = Fulfillment::query()->create([
                        'order_id' => $order->getKey(),
                        'status' => $fulfillmentData['status'],
                        'tracking_company' => $fulfillmentData['tracking_company'],
                        'tracking_number' => $fulfillmentData['tracking_number'],
                        'tracking_url' => null,
                        'shipped_at' => $this->timestamp($order, $fulfillmentData['shipped_at']),
                        'delivered_at' => $this->timestamp($order, $fulfillmentData['delivered_at']),
                    ]);

                    $this->createLines($fulfillment, $order, $fulfillmentData['lines']);
                }
            }
        });
    }

    /**
     * @param  array<string, int>|null  $lineQuantities
     */
    private function createLines(Fulfillment $fulfillment, Order $order, ?array $lineQuantities): void
    {
        $lines = $lineQuantities === null
            ? $order->lines
            : $order->lines->filter(function (OrderLine $line) use ($lineQuantities): bool {
                $handle = $line->product?->handle;

                return is_string($handle) && array_key_exists($handle, $lineQuantities);
            });

        $lines->each(function (OrderLine $line) use ($fulfillment, $lineQuantities): void {
            $handle = $line->product?->handle;
            $quantity = $lineQuantities === null || ! is_string($handle)
                ? $line->quantity
                : $lineQuantities[$handle];

            $fulfillment->lines()->create([
                'order_line_id' => $line->getKey(),
                'quantity' => $quantity,
            ]);
        });
    }

    private function timestamp(Order $order, mixed $value): mixed
    {
        if ($value === 'placed_at') {
            return $order->placed_at;
        }

        if (is_int($value)) {
            return now()->subDays($value);
        }

        return null;
    }

    /**
     * @return array<string, array<string, array{status: FulfillmentShipmentStatus, tracking_company: string|null, tracking_number: string|null, shipped_at: int|string|null, delivered_at: int|string|null, lines: array<string, int>|null}>>
     */
    private function fulfillments(): array
    {
        return [
            'acme-fashion' => [
                '#1002' => $this->fulfillment(FulfillmentShipmentStatus::Delivered, 'DHL', 'DHL1234567890', 8, 7),
                '#1003' => $this->fulfillment(FulfillmentShipmentStatus::Shipped, 'DHL', 'DHL9876543210', 3, null, [
                    'premium-slim-fit-jeans' => 1,
                ]),
                '#1007' => $this->fulfillment(FulfillmentShipmentStatus::Delivered, 'DHL', 'DHL1112223334', 18, 17),
                '#1008' => $this->fulfillment(FulfillmentShipmentStatus::Delivered, 'UPS', 'UPS5556667778', 10, 9),
                '#1011' => $this->fulfillment(FulfillmentShipmentStatus::Delivered, 'FedEx', 'FX9998887776', 23, 22),
                '#1014' => $this->fulfillment(FulfillmentShipmentStatus::Delivered, null, null, 'placed_at', 'placed_at'),
            ],
            'acme-electronics' => [
                '#5001' => $this->fulfillment(FulfillmentShipmentStatus::Delivered, 'DHL', 'DHL5001000001', 5, 4),
            ],
        ];
    }

    /**
     * @param  array<string, int>|null  $lines
     * @return array{status: FulfillmentShipmentStatus, tracking_company: string|null, tracking_number: string|null, shipped_at: int|string|null, delivered_at: int|string|null, lines: array<string, int>|null}
     */
    private function fulfillment(
        FulfillmentShipmentStatus $status,
        ?string $trackingCompany,
        ?string $trackingNumber,
        int|string|null $shippedAt,
        int|string|null $deliveredAt,
        ?array $lines = null,
    ): array {
        return [
            'status' => $status,
            'tracking_company' => $trackingCompany,
            'tracking_number' => $trackingNumber,
            'shipped_at' => $shippedAt,
            'delivered_at' => $deliveredAt,
            'lines' => $lines,
        ];
    }
}
