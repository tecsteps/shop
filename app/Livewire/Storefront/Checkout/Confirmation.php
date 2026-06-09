<?php

namespace App\Livewire\Storefront\Checkout;

use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::storefront')]
class Confirmation extends Component
{
    public int $checkoutId;

    public function mount(int $checkoutId): void
    {
        $this->checkoutId = $checkoutId;
    }

    public function render(): View
    {
        $order = Order::query()
            ->with(['lines.variant.product.media'])
            ->where('checkout_id', $this->checkoutId)
            ->firstOrFail();

        return view('livewire.storefront.checkout.confirmation', [
            'order' => $order,
            'items' => $this->itemData($order),
        ])->title(__('Order confirmation'));
    }

    /**
     * Presentation data for the order's lines.
     *
     * @return list<array{title: string, quantity: int, total_amount: int, image_url: string|null}>
     */
    protected function itemData(Order $order): array
    {
        return $order->lines
            ->map(function (OrderLine $line): array {
                $media = $line->variant?->product?->media->first();

                return [
                    'title' => $line->title_snapshot,
                    'quantity' => $line->quantity,
                    'total_amount' => $line->total_amount,
                    'image_url' => $media !== null ? Storage::disk('public')->url($media->storage_key) : null,
                ];
            })
            ->all();
    }
}
