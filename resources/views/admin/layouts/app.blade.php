<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script>
            (() => {
                const stored = localStorage.getItem('theme');
                const media = matchMedia('(prefers-color-scheme: dark)');
                const dark = stored ? stored === 'dark' : media.matches;
                document.documentElement.classList.toggle('dark', dark);
                document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            })();
        </script>
    </head>
    <body x-data="{ sidebarOpen: false }" @admin-sidebar-open.window="sidebarOpen = true" @keydown.escape.window="sidebarOpen = false" class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <a href="#admin-main" class="fixed left-3 top-3 z-[100] -translate-y-24 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white focus:translate-y-0">Skip to main content</a>

        <div class="fixed inset-y-0 left-0 z-50 hidden w-64 lg:block">
            <livewire:admin.layout.sidebar />
        </div>
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Admin navigation">
            <button type="button" class="absolute inset-0 bg-slate-950/60" @click="sidebarOpen = false" aria-label="Close navigation"></button>
            <div class="absolute inset-y-0 left-0 w-64 max-w-[85vw]" @click="if ($event.target.closest('a')) sidebarOpen = false" x-transition:enter="transition duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
                <livewire:admin.layout.sidebar />
            </div>
        </div>

        <div class="min-h-screen lg:pl-64">
            <livewire:admin.layout.top-bar />
            <main id="admin-main" class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8" tabindex="-1">
                @if(($breadcrumbs ?? []) !== [])
                    <nav class="mb-5 flex items-center gap-2 overflow-x-auto text-sm text-slate-500" aria-label="Breadcrumb">
                        <a href="{{ url('/admin') }}" wire:navigate class="hover:text-slate-900 dark:hover:text-white">Home</a>
                        @foreach($breadcrumbs as $crumb)
                            <span aria-hidden="true">/</span>
                            @if(! $loop->last && isset($crumb['url']))
                                <a href="{{ $crumb['url'] }}" wire:navigate class="whitespace-nowrap hover:text-slate-900 dark:hover:text-white">{{ $crumb['label'] }}</a>
                            @else
                                <span class="whitespace-nowrap text-slate-700 dark:text-slate-300" aria-current="page">{{ $crumb['label'] }}</span>
                            @endif
                        @endforeach
                    </nav>
                @endif
                {{ $slot }}
            </main>
        </div>

        <div x-data="{ toasts: [] }" @toast.window="const item={id:Date.now()+Math.random(),type:$event.detail.type??'info',message:$event.detail.message??''};toasts.push(item);setTimeout(()=>toasts=toasts.filter(t=>t.id!==item.id),5000)" class="pointer-events-none fixed right-4 top-20 z-[100] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2" aria-live="polite">
            <template x-for="toast in toasts" :key="toast.id">
                <div class="pointer-events-auto rounded-xl border border-slate-200 border-l-4 bg-white p-4 text-sm font-medium shadow-xl dark:border-slate-700 dark:bg-slate-900" :class="toast.type==='success'?'border-l-emerald-500':(toast.type==='error'?'border-l-red-500':'border-l-blue-500')">
                    <div class="flex items-start justify-between gap-4"><span x-text="toast.message"></span><button type="button" @click="toasts=toasts.filter(t=>t.id!==toast.id)" class="text-slate-400 hover:text-slate-700" aria-label="Dismiss notification">&times;</button></div>
                </div>
            </template>
        </div>
        @fluxScripts
    </body>
</html>
