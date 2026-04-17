<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Unavailable - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    <meta http-equiv="refresh" content="60">
    <style>html { scroll-behavior: smooth; }</style>
</head>
<body class="min-h-screen bg-white text-zinc-700 dark:bg-zinc-950 dark:text-zinc-300 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
            <svg class="h-8 w-8 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.049.58.025 1.193-.14 1.743" />
            </svg>
        </div>
        <h1 class="mt-6 text-2xl font-bold text-zinc-900 dark:text-white sm:text-3xl">Service temporarily unavailable</h1>
        <p class="mt-3 max-w-md text-base text-zinc-500 dark:text-zinc-400">
            We are performing scheduled maintenance and will be back shortly. This page will automatically refresh.
        </p>
        <p class="mt-6 text-sm text-zinc-400 dark:text-zinc-500">
            Thank you for your patience.
        </p>
    </div>
</body>
</html>
