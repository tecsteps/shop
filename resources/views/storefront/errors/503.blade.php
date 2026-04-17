<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-white px-4 dark:bg-gray-950">
    <div class="text-center">
        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ config('app.name') }}</p>
        <h1 class="mt-6 text-2xl font-bold text-gray-900 dark:text-white">We'll be back soon</h1>
        <p class="mt-2 max-w-sm text-gray-500 dark:text-gray-400">
            We're currently performing maintenance. Please check back shortly.
        </p>
    </div>
</body>
</html>
