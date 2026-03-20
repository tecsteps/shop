<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($title ?? '') ? $title . ' - ' : '' }}Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="h-full bg-gray-50 text-gray-700 dark:bg-gray-950 dark:text-gray-300 antialiased"
      x-data="{ sidebarOpen: false }">

    {{-- Skip to content --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:shadow-lg focus:ring-2 focus:ring-blue-500 dark:focus:bg-gray-800 dark:focus:text-white">
        Skip to main content
    </a>

    {{-- Sidebar --}}
    @livewire('admin.layout.sidebar')

    {{-- Main wrapper --}}
    <div class="lg:pl-64">
        {{-- Top bar --}}
        @livewire('admin.layout.top-bar')

        {{-- Toast notifications --}}
        <div x-data="{ toasts: [] }"
             x-init="
                 @if(session('toast'))
                     let ft = { id: Date.now(), type: '{{ session('toast.type') }}', message: '{{ session('toast.message') }}' };
                     toasts.push(ft);
                     setTimeout(() => toasts = toasts.filter(i => i.id !== ft.id), 5000);
                 @endif
             "
             x-on:toast.window="
                 let t = { id: Date.now(), ...$event.detail };
                 toasts.push(t);
                 setTimeout(() => toasts = toasts.filter(i => i.id !== t.id), 5000);
             "
             class="fixed right-4 top-16 z-50 flex flex-col gap-2">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-transition
                     class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-lg dark:border-gray-700 dark:bg-gray-800"
                     :class="{
                         'border-l-4 border-l-green-500': toast.type === 'success',
                         'border-l-4 border-l-red-500': toast.type === 'error',
                         'border-l-4 border-l-blue-500': toast.type === 'info',
                     }">
                    <span class="text-sm text-gray-900 dark:text-gray-100" x-text="toast.message"></span>
                    <button @click="toasts = toasts.filter(i => i.id !== toast.id)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </template>
        </div>

        {{-- Main content --}}
        <main id="main-content" class="p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>
    </div>

    @fluxScripts
    @livewireScripts
</body>
</html>
