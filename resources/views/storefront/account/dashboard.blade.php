<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('My Account') }}</title>
</head>
<body>
    <main class="mx-auto max-w-3xl px-6 py-16">
        <h1>{{ __('My Account') }}</h1>
        <p>{{ auth('customer')->user()?->name }}</p>

        <form method="POST" action="{{ route('account.logout') }}">
            @csrf
            <button type="submit">{{ __('Log out') }}</button>
        </form>
    </main>
</body>
</html>
