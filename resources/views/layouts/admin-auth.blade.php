<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin Login' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 flex items-center justify-center">
    <div class="w-full max-w-md px-6">
        <div class="mb-8 text-center">
            <flux:heading size="xl">Admin Panel</flux:heading>
        </div>

        {{ $slot }}
    </div>

    @fluxScripts
</body>
</html>
