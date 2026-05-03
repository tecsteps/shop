@php
    $store = app()->bound('current_store') ? app('current_store') : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-6 py-16">
        <div class="pointer-events-none absolute inset-x-0 top-1/2 -translate-y-1/2 text-center text-[10rem] font-semibold leading-none text-zinc-950/[0.035] dark:text-white/[0.055] sm:text-[16rem]">
            404
        </div>

        <div class="relative w-full max-w-xl text-center">
            @if($store)
                <a href="{{ route('home') }}" class="text-sm font-semibold text-zinc-500 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-white">{{ $store->name }}</a>
            @endif

            <h1 class="mt-4 text-3xl font-semibold tracking-normal sm:text-4xl">Page not found</h1>
            <p class="mx-auto mt-4 max-w-md text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                The page you're looking for doesn't exist or has been moved.
            </p>

            <form action="{{ route('storefront.search.index') }}" method="get" class="mx-auto mt-8 grid max-w-md gap-3 sm:grid-cols-[1fr_auto]">
                <label for="error-search" class="sr-only">Search products</label>
                <input id="error-search" name="q" type="search" placeholder="Search products" class="min-h-11 rounded-md border border-zinc-300 bg-white px-4 text-sm text-zinc-950 outline-none focus:ring-2 focus:ring-zinc-950 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:focus:ring-white">
                <button type="submit" class="min-h-11 rounded-md bg-zinc-950 px-5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">Search</button>
            </form>

            <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900">
                Return home
            </a>
        </div>
    </main>
</body>
</html>
