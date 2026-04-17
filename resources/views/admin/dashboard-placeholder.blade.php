<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dashboard</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <div class="p-8">
        <flux:heading size="xl">Dashboard</flux:heading>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Welcome to the admin panel.</p>

        <form method="POST" action="{{ route('admin.logout') }}" class="mt-4">
            @csrf
            <flux:button type="submit" variant="ghost">Sign out</flux:button>
        </form>
    </div>

    @fluxScripts
</body>
</html>
