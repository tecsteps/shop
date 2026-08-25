<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-800 antialiased dark:bg-zinc-900 dark:text-zinc-100">
        <div x-data="{ sidebarOpen: false }">
            {{-- Mobile backdrop --}}
            <div
                x-show="sidebarOpen"
                x-transition.opacity
                @click="sidebarOpen = false"
                class="fixed inset-0 z-40 bg-zinc-900/50 backdrop-blur-sm lg:hidden"
            ></div>

            {{-- Sidebar (fixed) --}}
            <div
                x-cloak
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 start-0 z-50 w-64 transition-transform duration-200 ease-in-out lg:translate-x-0"
            >
                <livewire:admin.layout.sidebar />
            </div>

            {{-- Main column --}}
            <div class="flex min-h-screen flex-col lg:ps-64">
                <livewire:admin.layout.top-bar />

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    <div class="mx-auto w-full max-w-7xl">
                        <livewire:admin.layout.breadcrumbs />

                        <div class="mt-4">
                            {{ $slot }}
                        </div>
                    </div>
                </main>

                <footer class="border-t border-zinc-200 px-4 py-4 text-center text-xs text-zinc-400 dark:border-zinc-700/60 dark:text-zinc-500">
                    {{ config('app.name') }} Admin
                </footer>
            </div>
        </div>

        {{-- Global toast notifications --}}
        <div
            x-data="toastStack()"
            @toast.window="add($event.detail)"
            class="pointer-events-none fixed end-4 top-4 z-[100] flex w-full max-w-sm flex-col gap-2"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    x-show="toast.visible"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-auto flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                    :class="{
                        'border-s-4 border-s-green-500': toast.type === 'success',
                        'border-s-4 border-s-red-500': toast.type === 'error',
                        'border-s-4 border-s-blue-500': toast.type === 'info',
                    }"
                >
                    <div class="flex-1 text-sm text-zinc-700 dark:text-zinc-200" x-text="toast.message"></div>
                    <button type="button" @click="remove(toast.id)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <flux:icon.x-mark variant="micro" />
                    </button>
                </div>
            </template>
        </div>

        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('toastStack', () => ({
                    toasts: [],
                    add(detail) {
                        const id = Date.now() + Math.random();

                        this.toasts.push({
                            id,
                            type: detail?.type ?? 'success',
                            message: detail?.message ?? '',
                            visible: false,
                        });

                        this.$nextTick(() => {
                            const toast = this.toasts.find((item) => item.id === id);
                            if (toast) {
                                toast.visible = true;
                            }
                        });

                        setTimeout(() => this.remove(id), 5000);
                    },
                    remove(id) {
                        this.toasts = this.toasts.filter((item) => item.id !== id);
                    },
                }));
            });
        </script>

        @fluxScripts
    </body>
</html>
