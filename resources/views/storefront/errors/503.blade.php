@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $storeName = $store?->name ?? config('app.name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __("We'll be back soon") }} - {{ $storeName }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white font-sans text-zinc-700 antialiased dark:bg-zinc-950 dark:text-zinc-300">
    <main class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
        <span class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $storeName }}</span>
        <h1 class="mt-8 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">{{ __("We'll be back soon") }}</h1>
        <p class="mt-3 max-w-md text-zinc-500 dark:text-zinc-400">
            {{ __("We're currently performing maintenance. Please check back shortly.") }}
        </p>
    </main>
</body>
</html>
