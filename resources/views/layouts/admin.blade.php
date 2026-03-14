<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        theme: localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'),
        init() {
            if (this.theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
        }
    }"
    :class="{ 'dark': theme === 'dark' }"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin' }}</title>

    <script>
        (function() {
            var theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        {{-- Mobile sidebar overlay --}}
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 z-40 lg:hidden"
            @click="sidebarOpen = false"
            x-cloak
            aria-hidden="true"
        ></div>

        {{-- Sidebar --}}
        <aside
            x-show="sidebarOpen"
            x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-zinc-800 border-r border-zinc-200 dark:border-zinc-700 overflow-y-auto lg:hidden"
            x-cloak
            role="dialog"
            aria-modal="true"
            aria-label="Admin navigation"
            @keydown.escape.window="sidebarOpen = false"
        >
            <livewire:admin.layout.sidebar />
        </aside>

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:flex lg:w-64 lg:flex-col bg-white dark:bg-zinc-800 border-r border-zinc-200 dark:border-zinc-700 overflow-y-auto">
            <livewire:admin.layout.sidebar />
        </aside>

        {{-- Main area --}}
        <div class="lg:ml-64">
            {{-- Top bar --}}
            <header class="sticky top-0 z-20 bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <flux:button variant="ghost" icon="bars-3" class="lg:hidden" @click="sidebarOpen = true" aria-label="Open navigation menu" />
                        <livewire:admin.layout.top-bar />
                    </div>
                </div>
            </header>

            {{-- Breadcrumbs --}}
            @if (isset($breadcrumbs))
                <div class="px-4 sm:px-6 lg:px-8 pt-4">
                    {{ $breadcrumbs }}
                </div>
            @endif

            {{-- Content --}}
            <main class="px-4 sm:px-6 lg:px-8 py-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast notifications --}}
    <div
        x-data="{
            toasts: [],
            addToast(event) {
                const id = Date.now();
                this.toasts.push({ id, type: event.detail.type || 'info', message: event.detail.message });
                setTimeout(() => this.removeToast(id), 5000);
            },
            removeToast(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        @toast.window="addToast($event)"
        class="fixed top-4 right-4 z-[100] space-y-2"
        role="status"
        aria-live="polite"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-4"
                class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 min-w-72"
                :class="{
                    'border-l-4 border-l-green-500': toast.type === 'success',
                    'border-l-4 border-l-red-500': toast.type === 'error',
                    'border-l-4 border-l-blue-500': toast.type === 'info'
                }"
            >
                <span class="text-sm text-zinc-700 dark:text-zinc-300" x-text="toast.message"></span>
                <button @click="removeToast(toast.id)" class="ml-auto text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" aria-label="Dismiss notification">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
        </template>
    </div>

    @fluxScripts
</body>
</html>
