@props(['title' => 'Admin'])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Shop</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-full bg-zinc-50 antialiased dark:bg-zinc-900">
    <main class="flex min-h-screen items-center justify-center p-6">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xs ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
            {{ $slot }}
        </div>
    </main>
    @fluxScripts
</body>
</html>
