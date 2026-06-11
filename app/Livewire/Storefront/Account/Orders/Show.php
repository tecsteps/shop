<?php

namespace App\Livewire\Storefront\Account\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::storefront')]
class Show extends Component
{
    public string $orderNumber = '';

    public function mount(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;

        $this->order();
    }

    public function render(): View
    {
        $order = $this->order();

        return view('livewire.storefront.account.orders.show', [
            'order' => $order,
            'lines' => $this->lineData($order),
            'timeline' => $this->timeline($order),
        ])->title(__('Order :number', ['number' => $order->order_number]));
    }

    /**
     * The customer's order matching the route's order number. Order numbers
     * are stored with a configurable prefix (e.g. "#1042") that cannot
     * appear in a URL path, so the bare number is matched too. Orders of
     * other customers are never found (404).
     */
    protected function order(): Order
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return $customer->orders()
            ->whereIn('order_number', [$this->orderNumber, '#'.$this->orderNumber])
            ->with(['lines.variant.product.media', 'lines.variant.optionValues', 'payments', 'fulfillments'])
            ->firstOrFail();
    }

    /**
     * Presentation data for the order's line items.
     *
     * @return list<array{title: string, variant_label: string, sku: string|null, quantity: int, unit_price_amount: int, total_amount: int, image_url: string|null}>
     */
    protected function lineData(Order $order): array
    {
        return $order->lines
            ->map(function (OrderLine $line): array {
                $media = $line->variant?->product?->media->first();

                return [
                    'title' => $line->title_snapshot,
                    'variant_label' => $line->variant?->optionValues->pluck('value')->implode(' / ') ?? '',
                    'sku' => $line->sku_snapshot,
                    'quantity' => $line->quantity,
                    'unit_price_amount' => $line->unit_price_amount,
                    'total_amount' => $line->total_amount,
                    'image_url' => $media !== null ? Storage::disk('public')->url($media->storage_key) : null,
                ];
            })
            ->all();
    }

    /**
     * Chronological order history built from the order itself (placed,
     * cancelled), its payments (paid), and its fulfillments (fulfilled,
     * delivered).
     *
     * @return list<array{label: string, description: string|null, timestamp: Carbon}>
     */
    protected function timeline(Order $order): array
    {
        $events = [];

        if ($order->placed_at !== null) {
            $events[] = [
                'label' => __('Order placed'),
                'description' => null,
                'timestamp' => $order->placed_at,
            ];
        }

        $capturedPayment = $order->payments->firstWhere('status', PaymentStatus::Captured);

        if ($capturedPayment !== null) {
            $events[] = [
                'label' => __('Payment received'),
                'description' => __('Paid via :method', ['method' => str_replace('_', ' ', $capturedPayment->method->value)]),
                'timestamp' => $capturedPayment->created_at,
            ];
        }

        foreach ($order->fulfillments as $fulfillment) {
            $tracking = filled($fulfillment->tracking_number)
                ? trim(($fulfillment->tracking_company ?? '').' '.$fulfillment->tracking_number)
                : null;

            $events[] = [
                'label' => __('Items fulfilled'),
                'description' => $tracking !== null ? __('Tracking: :tracking', ['tracking' => $tracking]) : null,
                'timestamp' => $fulfillment->shipped_at ?? $fulfillment->created_at,
            ];

            if ($fulfillment->delivered_at !== null) {
                $events[] = [
                    'label' => __('Delivered'),
                    'description' => null,
                    'timestamp' => $fulfillment->delivered_at,
                ];
            }
        }

        if ($order->status === OrderStatus::Cancelled) {
            $events[] = [
                'label' => __('Order cancelled'),
                'description' => null,
                'timestamp' => $order->updated_at,
            ];
        }

        usort($events, fn (array $a, array $b): int => $a['timestamp'] <=> $b['timestamp']);

        return $events;
    }
}
