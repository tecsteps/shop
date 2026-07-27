<x-mail::message>
# Your refund has been processed

We've issued a refund of **{{ \App\Support\Money::format($refund->amount, $order->currency) }}** for your order **{{ $order->order_number }}** at **{{ $order->store->name }}**.

@if ($refund->reason)
<x-mail::panel>
**Reason:** {{ $refund->reason }}
</x-mail::panel>
@endif

The amount will be credited back to your original payment method. Depending on your bank, this may take a few business days.

Thanks,<br>
{{ $order->store->name }}
</x-mail::message>
