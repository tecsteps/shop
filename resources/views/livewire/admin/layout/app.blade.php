<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Admin' }} - {{ $currentStore?->name ?? config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="alternate icon" href="/favicon.ico" />
    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-100 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <a href="#admin-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-zinc-900 focus:px-4 focus:py-2 focus:text-white focus:shadow-lg">Skip to main content</a>

    <livewire:admin.layout.sidebar />

    <div class="lg:pl-64">
        <livewire:admin.layout.top-bar />

        <main id="admin-content" class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>

    <div
        x-data="{ toasts: [] }"
        x-on:toast.window="toasts.push({ id: Date.now() + Math.random(), type: $event.detail.type ?? 'info', message: $event.detail.message ?? '' }); setTimeout(() => toasts.shift(), 5000)"
        class="pointer-events-none fixed right-4 top-4 z-50 flex w-80 flex-col gap-2"
        aria-live="polite"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                class="pointer-events-auto rounded-lg border-l-4 bg-white p-4 shadow-lg dark:bg-zinc-900"
                :class="toast.type === 'success' ? 'border-green-500' : (toast.type === 'error' ? 'border-red-500' : 'border-blue-500')"
            >
                <p class="text-sm font-medium" x-text="toast.message"></p>
            </div>
        </template>
        @if (session('toast'))
            <div
                x-data="{ show: true }"
                x-init="setTimeout(() => show = false, 5000)"
                x-show="show"
                class="pointer-events-auto rounded-lg border-l-4 bg-white p-4 shadow-lg dark:bg-zinc-900 {{ session('toast')['type'] === 'success' ? 'border-green-500' : (session('toast')['type'] === 'error' ? 'border-red-500' : 'border-blue-500') }}"
            >
                <p class="text-sm font-medium">{{ session('toast')['message'] }}</p>
            </div>
        @endif
    </div>

    @livewireScripts
    @fluxScripts
</body>
</html>
