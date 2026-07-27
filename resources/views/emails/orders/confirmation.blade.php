<x-mail::message>
# Thank you for your order!

Hi from **{{ $order->store->name }}** — your order **{{ $order->order_number }}** has been received.

<x-mail::table>
| Item | Qty | Total |
|:-----|----:|------:|
@foreach ($order->lines as $line)
| {{ $line->title_snapshot }}@if ($line->sku_snapshot) ({{ $line->sku_snapshot }})@endif | {{ $line->quantity }} | {{ \App\Support\Money::format($line->total_amount, $order->currency) }} |
@endforeach
</x-mail::table>

<x-mail::table>
| Subtotal | {{ \App\Support\Money::format($order->subtotal_amount, $order->currency) }} |
|:---------|-----:|
@if ($order->discount_amount > 0)
| Discount | -{{ \App\Support\Money::format($order->discount_amount, $order->currency) }} |
@endif
| Shipping | {{ \App\Support\Money::format($order->shipping_amount, $order->currency) }} |
| Tax | {{ \App\Support\Money::format($order->tax_amount, $order->currency) }} |
| **Total** | **{{ \App\Support\Money::format($order->total_amount, $order->currency) }}** |
</x-mail::table>

@if ($order->payment_method === \App\Enums\PaymentMethod::BankTransfer)
<x-mail::panel>
**Bank Transfer Instructions**

Please transfer **{{ \App\Support\Money::format($order->total_amount, $order->currency) }}** to:

Bank: Mock Bank AG
IBAN: DE89 3704 0044 0532 0130 00
BIC: COBADEFFXXX
Reference: {{ $order->order_number }}

Your order will be processed once payment is confirmed.
</x-mail::panel>
@endif

@php($shipping = $order->shipping_address_json ?? [])
@if ($shipping !== [])
**Shipping address**

{{ ($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '') }}
{{ $shipping['address1'] ?? '' }}@if (! empty($shipping['address2'])), {{ $shipping['address2'] }}@endif
{{ ($shipping['postal_code'] ?? '').' '.($shipping['city'] ?? '') }}
{{ $shipping['country_code'] ?? $shipping['country'] ?? '' }}
@endif

@php($billing = $order->billing_address_json ?? [])
@if ($billing !== [])
**Billing address**

{{ ($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '') }}
{{ $billing['address1'] ?? '' }}@if (! empty($billing['address2'])), {{ $billing['address2'] }}@endif
{{ ($billing['postal_code'] ?? '').' '.($billing['city'] ?? '') }}
{{ $billing['country_code'] ?? $billing['country'] ?? '' }}
@endif

Thanks,<br>
{{ $order->store->name }}
</x-mail::message>
