<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-white antialiased dark:bg-zinc-900">
    <div class="px-4 text-center">
        <div class="text-4xl font-bold text-zinc-900 dark:text-white">{{ config('app.name') }}</div>
        <h1 class="mt-6 text-2xl font-bold text-zinc-900 dark:text-white">We'll be back soon</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">We are performing scheduled maintenance. Please check back shortly.</p>
    </div>
</body>
</html>
