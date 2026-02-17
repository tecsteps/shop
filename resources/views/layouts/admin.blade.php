<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin' }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles

    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    <div
        x-data="{ sidebarOpen: false }"
        class="flex min-h-screen"
        x-on:toast.window="
            $dispatch('notify', { type: $event.detail.type, message: $event.detail.message })
        "
    >
        {{-- Mobile sidebar overlay --}}
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            x-on:click="sidebarOpen = false"
        ></div>

        {{-- Sidebar --}}
        <aside
            x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 transform bg-white transition-transform duration-300 ease-in-out dark:bg-zinc-800 lg:static lg:translate-x-0"
        >
            @livewire('admin.layout.sidebar')
        </aside>

        {{-- Main content --}}
        <div class="flex min-w-0 flex-1 flex-col">
            {{-- Top bar --}}
            @livewire('admin.layout.top-bar')

            {{-- Breadcrumbs & content --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast notifications --}}
    <div
        x-data="{
            toasts: [],
            add(type, message) {
                const id = Date.now();
                this.toasts.push({ id, type, message });
                setTimeout(() => this.remove(id), 5000);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        x-on:toast.window="add($event.detail.type, $event.detail.message)"
        class="pointer-events-none fixed right-4 top-4 z-[100] flex flex-col gap-2"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                class="pointer-events-auto w-80 overflow-hidden rounded-lg bg-white shadow-lg dark:bg-zinc-700"
            >
                <div class="flex items-start gap-3 p-4">
                    <div
                        class="mt-0.5 h-2 w-2 shrink-0 rounded-full"
                        x-bind:class="{
                            'bg-green-500': toast.type === 'success',
                            'bg-red-500': toast.type === 'error',
                            'bg-blue-500': toast.type === 'info'
                        }"
                    ></div>
                    <p class="text-sm text-zinc-700 dark:text-zinc-200" x-text="toast.message"></p>
                    <button
                        class="ml-auto shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                        x-on:click="remove(toast.id)"
                    >
                        <flux:icon name="x-mark" variant="mini" />
                    </button>
                </div>
            </div>
        </template>
    </div>

    @fluxScripts
    @livewireScripts
</body>
</html>
