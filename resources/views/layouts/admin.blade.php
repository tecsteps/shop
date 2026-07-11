<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>@include('partials.head')</head>
    <body class="min-h-screen bg-zinc-50 text-zinc-950 antialiased dark:bg-zinc-950 dark:text-zinc-50">
        <div x-data="{ menuOpen: false, toasts: [] }" @toast.window="toasts.push({ id: Date.now(), ...$event.detail }); setTimeout(() => toasts.shift(), 5000)" class="min-h-screen">
            <div x-show="menuOpen" x-transition.opacity @click="menuOpen = false" class="fixed inset-0 z-40 bg-zinc-950/50 lg:hidden" aria-hidden="true"></div>
            <aside :class="menuOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 start-0 z-50 w-64 border-e border-zinc-200 bg-white transition-transform dark:border-zinc-800 dark:bg-zinc-900 lg:translate-x-0"><livewire:admin.layout.sidebar /></aside>
            <div class="lg:ps-64">
                <header class="sticky top-0 z-30 border-b border-zinc-200 bg-white/95 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95"><livewire:admin.layout.top-bar /></header>
                <main id="main-content" class="mx-auto w-full max-w-screen-2xl p-4 sm:p-6 lg:p-8">{{ $slot }}</main>
            </div>
            <div class="fixed end-4 top-20 z-50 flex w-[min(24rem,calc(100vw-2rem))] flex-col gap-2" aria-live="polite" role="status">
                <template x-for="toast in toasts" :key="toast.id"><div class="rounded-xl border border-zinc-200 border-s-4 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-900" :class="{ 'border-s-emerald-500': toast.type === 'success', 'border-s-red-500': toast.type === 'error', 'border-s-blue-500': toast.type === 'info' }" x-text="toast.message"></div></template>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
