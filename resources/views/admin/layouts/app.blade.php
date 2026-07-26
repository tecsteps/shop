<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Apply the stored theme before first paint (spec 03 §1.6).
        (function () {
            const stored = localStorage.getItem('theme');
            const dark = stored === 'dark'
                || (stored === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-100 focus:rounded-md focus:bg-blue-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-950">
        Skip to main content
    </a>

    <div x-data="{ sidebarOpen: false }">
        {{-- Mobile sidebar backdrop --}}
        <div x-show="sidebarOpen" x-cloak
             x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-zinc-900/50 lg:hidden dark:bg-black/60" aria-hidden="true"></div>

        {{-- Sidebar: fixed 256px on desktop, slide-over on mobile (spec 03 §1.2) --}}
        <aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-zinc-200 bg-white transition-transform duration-200 ease-out lg:translate-x-0 dark:border-zinc-800 dark:bg-zinc-900"
               :class="{ 'translate-x-0': sidebarOpen }"
               @keydown.escape.window="sidebarOpen = false"
               aria-label="Admin navigation">
            <livewire:admin.layout.sidebar />
        </aside>

        <div class="flex min-h-screen flex-col lg:pl-64">
            <livewire:admin.layout.top-bar />

            <main id="main-content" class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <livewire:admin.layout.breadcrumbs />

                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast notifications (spec 03 §1.5, §20) --}}
    <div x-data="{
            toasts: [],
            add(detail) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, type: detail.type ?? 'info', message: detail.message ?? '' });
                setTimeout(() => this.remove(id), 5000);
            },
            remove(id) {
                this.toasts = this.toasts.filter((toast) => toast.id !== id);
            },
        }"
         @toast.window="add($event.detail)"
         @if (session('toast')) x-init="add(@js(session('toast')))" @endif
         class="pointer-events-none fixed top-4 right-4 z-100 flex w-full max-w-sm flex-col gap-2"
         aria-live="polite">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="pointer-events-auto flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                 :class="{
                    'border-l-4 border-l-green-500': toast.type === 'success',
                    'border-l-4 border-l-red-500': toast.type === 'error',
                    'border-l-4 border-l-blue-500': toast.type === 'info',
                 }"
                 role="alert">
                <flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-green-500" x-show="toast.type === 'success'" />
                <flux:icon name="exclamation-circle" class="mt-0.5 size-5 shrink-0 text-red-500" x-show="toast.type === 'error'" />
                <flux:icon name="information-circle" class="mt-0.5 size-5 shrink-0 text-blue-500" x-show="toast.type === 'info'" />
                <p class="flex-1 text-sm text-zinc-900 dark:text-zinc-100" x-text="toast.message"></p>
                <button type="button" @click="remove(toast.id)" class="rounded p-0.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" aria-label="Dismiss">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
        </template>
    </div>

    @fluxScripts
</body>
</html>
