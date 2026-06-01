@php
    $store = app()->bound('current_store') ? app('current_store') : null;
    $storeName = $store?->name ?? config('app.name');
    $homeUrl = $store !== null ? url('/') : url('/');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Page not found') }} - {{ $storeName }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white font-sans text-zinc-700 antialiased dark:bg-zinc-950 dark:text-zinc-300">
    <main class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
        <p class="select-none text-[8rem] font-black leading-none text-zinc-100 dark:text-zinc-900 sm:text-[12rem]" aria-hidden="true">404</p>
        <h1 class="-mt-8 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">{{ __('Page not found') }}</h1>
        <p class="mt-3 max-w-md text-zinc-500 dark:text-zinc-400">
            {{ __("The page you're looking for doesn't exist or has been moved.") }}
        </p>

        <form action="{{ url('/search') }}" method="GET" class="mt-8 flex w-full max-w-sm gap-2">
            <input type="search" name="q" placeholder="{{ __('Search') }}"
                   class="flex-1 rounded-lg border border-zinc-300 bg-white px-4 py-2 text-zinc-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" />
            <button type="submit"
                    class="rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition hover:bg-blue-700">
                {{ __('Search') }}
            </button>
        </form>

        <a href="{{ $homeUrl }}"
           class="mt-4 inline-flex items-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
            {{ __('Go to home page') }}
        </a>
    </main>
</body>
</html>
