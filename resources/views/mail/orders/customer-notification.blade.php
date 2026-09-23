<h1>
    @switch($notificationType)
        @case(\App\Mail\CustomerOrderNotification::ORDER_CONFIRMATION)
            Thank you for your order
            @break
        @case(\App\Mail\CustomerOrderNotification::REFUND)
            Your refund has been processed
            @break
        @case(\App\Mail\CustomerOrderNotification::SHIPPED)
            Your order has shipped
            @break
        @case(\App\Mail\CustomerOrderNotification::CANCELLED)
            Your order has been cancelled
            @break
    @endswitch
</h1>

<p>Order {{ $order->order_number }}</p>

@if ($notificationType === \App\Mail\CustomerOrderNotification::ORDER_CONFIRMATION)
    <p>We have received your order. Its current payment status is {{ str_replace('_', ' ', $order->financial_status) }}.</p>
    <h2>Order summary</h2>
    <ul>
        @foreach ($order->lines as $line)
            <li>{{ $line->title_snapshot }}@if ($line->variant_title_snapshot) — {{ $line->variant_title_snapshot }}@endif, {{ $line->quantity }} × {{ number_format($line->unit_price_amount / 100, 2) }} {{ $order->currency }}</li>
        @endforeach
    </ul>
    <p>Total: {{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</p>
    @if ($order->payment_method === 'bank_transfer' && $order->financial_status === 'pending')
        <h2>Bank transfer instructions</h2>
        <p>Bank: Mock Bank AG<br>IBAN: DE89 3704 0044 0532 0130 00<br>BIC: COBADEFFXXX<br>Reference: {{ $order->order_number }}<br>Amount: {{ number_format($order->total_amount / 100, 2) }} {{ $order->currency }}</p>
    @endif
@elseif ($notificationType === \App\Mail\CustomerOrderNotification::REFUND)
    <p>A refund of {{ number_format(($details['amount'] ?? 0) / 100, 2) }} {{ $order->currency }} has been processed for your order.</p>
    @if (filled($details['reason'] ?? null))
        <p>Reason: {{ $details['reason'] }}</p>
    @endif
@elseif ($notificationType === \App\Mail\CustomerOrderNotification::SHIPPED)
    <p>Your items are on their way.</p>
    @if (filled($details['tracking_company'] ?? null))
        <p>Carrier: {{ $details['tracking_company'] }}</p>
    @endif
    @if (filled($details['tracking_number'] ?? null))
        <p>Tracking number: {{ $details['tracking_number'] }}</p>
    @endif
    @if (filter_var($details['tracking_url'] ?? null, FILTER_VALIDATE_URL))
        <p><a href="{{ $details['tracking_url'] }}">Track your shipment</a></p>
    @endif
@elseif ($notificationType === \App\Mail\CustomerOrderNotification::CANCELLED)
    <p>Your order was cancelled. Any reserved inventory has been released.</p>
@endif
