<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Admin Dashboard') }}</title>
</head>
<body>
    <main class="mx-auto max-w-5xl px-6 py-10">
        <h1>{{ __('Dashboard') }}</h1>
        <p>{{ $currentStore->name ?? '' }}</p>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit">{{ __('Log out') }}</button>
        </form>
    </main>
</body>
</html>
