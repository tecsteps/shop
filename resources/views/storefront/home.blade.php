<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $currentStore->name ?? config('app.name') }}</title>
</head>
<body>
    <main class="mx-auto max-w-3xl px-6 py-16 text-center">
        <h1>{{ $currentStore->name }}</h1>
        <p>{{ __('Welcome to our store.') }}</p>
    </main>
</body>
</html>
