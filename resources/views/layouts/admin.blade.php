<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
    x-data="{ darkMode: localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
    :class="{ 'dark': darkMode }">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside
            class="hidden w-64 -translate-x-full fixed inset-y-0 left-0 z-50 overflow-y-auto border-r border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 lg:static lg:z-auto lg:block lg:translate-x-0"
            :class="{ 'translate-x-0 !block': sidebarOpen }"
        >
            <livewire:admin.layout.sidebar />
        </aside>

        {{-- Sidebar backdrop (mobile) --}}
        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 bg-black/50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        {{-- Main content --}}
        <div class="flex flex-1 flex-col min-w-0">
            {{-- Top bar --}}
            <livewire:admin.layout.top-bar />

            {{-- Page content --}}
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toast notification system --}}
    <div
        x-data="{
            toasts: [],
            add(toast) {
                const id = Date.now();
                this.toasts.push({ id, ...toast });
                setTimeout(() => this.remove(id), 5000);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        @toast.window="add($event.detail)"
        class="fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-4"
                class="rounded-lg border bg-white shadow-lg dark:bg-zinc-800 dark:border-zinc-700 overflow-hidden"
            >
                <div class="flex items-start gap-3 p-4">
                    <div
                        class="w-1 self-stretch rounded-full shrink-0"
                        :class="{
                            'bg-green-500': toast.type === 'success',
                            'bg-red-500': toast.type === 'error',
                            'bg-blue-500': toast.type === 'info'
                        }"
                    ></div>
                    <p class="text-sm text-zinc-700 dark:text-zinc-300" x-text="toast.message"></p>
                    <button @click="remove(toast.id)" class="ml-auto shrink-0 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    @fluxScripts
</body>
</html>
