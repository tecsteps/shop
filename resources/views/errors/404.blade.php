<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <style>html { scroll-behavior: smooth; }</style>
</head>
<body class="min-h-screen bg-white text-zinc-700 dark:bg-zinc-950 dark:text-zinc-300 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 text-center">
        <p class="text-6xl font-bold text-zinc-300 dark:text-zinc-700 sm:text-8xl" aria-hidden="true">404</p>
        <h1 class="mt-4 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">Page not found</h1>
        <p class="mt-3 max-w-md text-base text-zinc-500 dark:text-zinc-400">
            Sorry, we could not find the page you are looking for. It may have been moved or no longer exists.
        </p>
        <a href="/"
           class="mt-8 inline-flex items-center rounded-md bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 transition-colors dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100 dark:focus-visible:ring-offset-zinc-950">
            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Back to homepage
        </a>
    </div>
</body>
</html>
