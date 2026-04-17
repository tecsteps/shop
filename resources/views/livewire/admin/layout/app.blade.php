<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ isset($title) ? $title . ' - Admin' : 'Admin' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
        @fluxAppearance
        @livewireStyles
    </head>
    <body class="min-h-screen bg-gray-50 text-gray-700 antialiased dark:bg-gray-950 dark:text-gray-300">
        {{-- Skip Link --}}
        <a href="#admin-main-content"
           class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-gray-900 focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-gray-900 dark:focus:text-white">
            Skip to main content
        </a>

        {{-- Toast Notifications --}}
        <div x-data="{ toasts: [] }"
             @toast.window="
                 const toast = { id: Date.now(), ...$event.detail };
                 toasts.push(toast);
                 setTimeout(() => { toasts = toasts.filter(t => t.id !== toast.id) }, 5000);
             "
             class="fixed right-4 top-4 z-[100] flex flex-col gap-2"
             x-cloak>
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition:enter="transition duration-300 ease-out"
                     x-transition:enter-start="translate-x-full opacity-0"
                     x-transition:enter-end="translate-x-0 opacity-100"
                     x-transition:leave="transition duration-200 ease-in"
                     x-transition:leave-start="translate-x-0 opacity-100"
                     x-transition:leave-end="translate-x-full opacity-0"
                     class="flex min-w-[300px] items-center gap-3 rounded-lg border bg-white p-4 shadow-lg dark:bg-gray-900"
                     :class="{
                         'border-l-4 border-l-green-500 border-gray-200 dark:border-gray-800': toast.type === 'success',
                         'border-l-4 border-l-red-500 border-gray-200 dark:border-gray-800': toast.type === 'error',
                         'border-l-4 border-l-blue-500 border-gray-200 dark:border-gray-800': toast.type === 'info',
                     }">
                    <span class="text-sm text-gray-900 dark:text-white" x-text="toast.message"></span>
                    <button @click="toasts = toasts.filter(t => t.id !== toast.id)" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <div class="flex min-h-screen">
            {{-- Sidebar --}}
            <livewire:admin.layout.sidebar />

            {{-- Main Content --}}
            <div class="flex flex-1 flex-col lg:ml-64">
                {{-- Top Bar --}}
                <livewire:admin.layout.top-bar />

                {{-- Page Content --}}
                <main id="admin-main-content" class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @fluxScripts
        @livewireScripts
    </body>
</html>
