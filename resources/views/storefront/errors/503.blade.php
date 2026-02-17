<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance Mode</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-white antialiased dark:bg-gray-950">
    <div class="px-4 text-center">
        <p class="text-xl font-bold text-gray-900 dark:text-white">
            {{ config('app.name') }}
        </p>
        <h1 class="mt-6 text-xl font-bold text-gray-900 dark:text-white">We'll be back soon</h1>
        <p class="mx-auto mt-2 max-w-sm text-sm text-gray-500 dark:text-gray-400">
            We're currently performing maintenance. Please check back shortly.
        </p>
    </div>
</body>
</html>
