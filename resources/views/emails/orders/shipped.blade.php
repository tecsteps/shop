<x-mail::message>
# Your order has shipped

Good news from **{{ $order->store->name }}** — your order **{{ $order->order_number }}** is on its way.

@if ($fulfillment->tracking_company || $fulfillment->tracking_number)
<x-mail::panel>
**Tracking information**

@if ($fulfillment->tracking_company)
Carrier: {{ $fulfillment->tracking_company }}
@endif
@if ($fulfillment->tracking_number)
Tracking number: {{ $fulfillment->tracking_number }}
@endif
</x-mail::panel>
@endif

@if ($fulfillment->tracking_url)
<x-mail::button :url="$fulfillment->tracking_url">
Track your shipment
</x-mail::button>
@endif

**Items in this shipment**

<x-mail::table>
| Item | Qty |
|:-----|----:|
@foreach ($fulfillment->lines as $line)
| {{ $line->orderLine?->title_snapshot ?? 'Item' }} | {{ $line->quantity }} |
@endforeach
</x-mail::table>

Thanks,<br>
{{ $order->store->name }}
</x-mail::message>
