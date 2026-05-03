@php
    $address = $address ?? [];
@endphp

<address class="not-italic leading-6">
    @if(($address['first_name'] ?? null) || ($address['last_name'] ?? null))
        <div>{{ trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? '')) }}</div>
    @endif
    @if($address['company'] ?? null)
        <div>{{ $address['company'] }}</div>
    @endif
    @if($address['address1'] ?? null)
        <div>{{ $address['address1'] }}</div>
    @endif
    @if($address['address2'] ?? null)
        <div>{{ $address['address2'] }}</div>
    @endif
    @if(($address['postal_code'] ?? null) || ($address['city'] ?? null))
        <div>{{ trim(($address['postal_code'] ?? '').' '.($address['city'] ?? '')) }}</div>
    @endif
    @if($address['country'] ?? $address['country_code'] ?? null)
        <div>{{ $address['country'] ?? $address['country_code'] }}</div>
    @endif
</address>
