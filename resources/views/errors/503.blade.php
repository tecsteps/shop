@php
    $store = app()->bound('current_store') ? app('current_store') : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Store unavailable</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
    <main class="flex min-h-screen items-center justify-center px-6 py-16">
        <div class="w-full max-w-lg text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-md bg-zinc-950 text-lg font-semibold text-white dark:bg-white dark:text-zinc-950">
                {{ str($store?->name ?? config('app.name'))->substr(0, 1)->upper() }}
            </div>

            @if($store)
                <p class="mt-5 text-sm font-semibold text-zinc-500 dark:text-zinc-400">{{ $store->name }}</p>
            @endif

            <h1 class="mt-3 text-3xl font-semibold tracking-normal sm:text-4xl">We'll be back soon</h1>
            <p class="mx-auto mt-4 max-w-md text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                We're currently performing maintenance. Please check back shortly.
            </p>
        </div>
    </main>
</body>
</html>
