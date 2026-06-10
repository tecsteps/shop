<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => isset($title) && filled($title) ? $title.' - '.__('Admin') : __('Admin')])
    </head>
    <body class="min-h-screen bg-zinc-100 antialiased dark:bg-zinc-950">
        <livewire:admin.layout.sidebar />

        <div class="flex min-h-screen flex-col lg:pl-64">
            <livewire:admin.layout.top-bar />

            <main class="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>

        {{-- Global toast notifications (spec 03 section 1.5): top-right, auto-dismiss, stacking. --}}
        <div
            x-data="{
                toasts: [],
                add(toast) {
                    if (! toast || ! toast.message) return;
                    const id = Date.now() + Math.random();
                    this.toasts.push({ id, type: toast.type ?? 'info', message: toast.message });
                    setTimeout(() => this.dismiss(id), 5000);
                },
                dismiss(id) {
                    this.toasts = this.toasts.filter((toast) => toast.id !== id);
                },
            }"
            x-init="add(@js(session('toast')))"
            x-on:toast.window="add($event.detail)"
            class="pointer-events-none fixed top-4 right-4 z-[70] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2"
            aria-live="polite"
        >
            <template x-for="toast in toasts" :key="toast.id">
                <div
                    x-transition.opacity
                    class="pointer-events-auto flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
                    :class="{
                        'border-l-4 border-l-green-500': toast.type === 'success',
                        'border-l-4 border-l-red-500': toast.type === 'error',
                        'border-l-4 border-l-blue-500': toast.type === 'info',
                    }"
                >
                    <p class="flex-1 text-sm text-zinc-800 dark:text-zinc-100" x-text="toast.message"></p>
                    <button
                        type="button"
                        class="cursor-pointer text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                        x-on:click="dismiss(toast.id)"
                        aria-label="{{ __('Dismiss') }}"
                    >
                        <flux:icon name="x-mark" variant="micro" />
                    </button>
                </div>
            </template>
        </div>

        @fluxScripts
    </body>
</html>
