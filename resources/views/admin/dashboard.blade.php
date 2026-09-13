<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin</title>
</head>
<body>
    {{-- Placeholder dashboard. Replaced by the Phase 7 admin panel (Livewire). --}}
    <h1>Admin Dashboard</h1>
    <p>Store: {{ $currentStore->name ?? 'none' }}</p>

    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>
