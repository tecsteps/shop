<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account</title>
</head>
<body>
    {{-- Placeholder account page. Replaced by the Phase 6 customer account UI. --}}
    <h1>My Account</h1>
    <p>Store: {{ $currentStore->name ?? 'none' }}</p>

    <form method="POST" action="{{ route('customer.logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
