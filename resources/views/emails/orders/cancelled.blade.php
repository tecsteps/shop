<x-mail::message>
# Your order has been cancelled

Your order **{{ $order->order_number }}** at **{{ $order->store->name }}** has been cancelled.

@if ($reason)
<x-mail::panel>
**Reason:** {{ $reason }}
</x-mail::panel>
@endif

@if ($order->lines->isNotEmpty())
<x-mail::table>
| Item | Qty |
|:-----|----:|
@foreach ($order->lines as $line)
| {{ $line->title_snapshot }} | {{ $line->quantity }} |
@endforeach
</x-mail::table>
@endif

If you did not request this cancellation or have questions, please contact us.

Thanks,<br>
{{ $order->store->name }}
</x-mail::message>
