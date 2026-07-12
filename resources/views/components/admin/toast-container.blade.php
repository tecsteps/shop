@props([
    'duration' => 5000,
    'initial' => [],
])

@php
    $duration = max(1000, min(30000, (int) $duration));
    $initial = collect($initial)
        ->map(fn (mixed $toast): array => [
            'type' => in_array(data_get($toast, 'type'), ['success', 'error', 'info'], true) ? data_get($toast, 'type') : 'info',
            'message' => (string) data_get($toast, 'message', ''),
        ])
        ->filter(fn (array $toast): bool => $toast['message'] !== '')
        ->values()
        ->all();
@endphp

<div
    x-data="{
        toasts: [],
        duration: @js($duration),
        push(detail) {
            const allowedTypes = ['success', 'error', 'info'];
            const toast = {
                id: Date.now() + Math.random(),
                type: allowedTypes.includes(detail?.type) ? detail.type : 'info',
                message: String(detail?.message ?? ''),
            };

            if (! toast.message) return;

            this.toasts.push(toast);
            window.setTimeout(() => this.remove(toast.id), this.duration);
        },
        remove(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    }"
    x-init="@js($initial).forEach((toast) => push(toast))"
    x-on:toast.window="push($event.detail)"
    {{ $attributes->class('admin-toast-region') }}
    aria-live="polite"
    aria-relevant="additions"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="admin-toast"
            x-bind:class="{
                'admin-toast-success': toast.type === 'success',
                'admin-toast-error': toast.type === 'error',
                'admin-toast-info': toast.type === 'info',
            }"
            x-bind:role="toast.type === 'error' ? 'alert' : 'status'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-4 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-4 opacity-0"
        >
            <svg x-show="toast.type === 'success'" aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.051l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.142Z" clip-rule="evenodd" /></svg>
            <svg x-show="toast.type === 'error'" aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.72 7.22a.75.75 0 0 0-1.06 1.06L9.38 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10.44 11l1.72 1.78a.75.75 0 1 0 1.08-1.04L11.48 10l1.76-1.74a.75.75 0 1 0-1.06-1.06L10.44 8.94 8.72 7.22Z" clip-rule="evenodd" /></svg>
            <svg x-show="toast.type === 'info'" aria-hidden="true" class="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0ZM9.25 8.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm.75-3a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" clip-rule="evenodd" /></svg>

            <p class="min-w-0 flex-1 text-sm font-medium text-zinc-800 dark:text-zinc-100" x-text="toast.message"></p>
            <button type="button" class="-m-2 inline-grid size-9 shrink-0 place-items-center rounded-lg text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 focus-visible:outline-2 focus-visible:outline-blue-600 dark:hover:bg-zinc-800 dark:hover:text-white" x-on:click="remove(toast.id)" aria-label="{{ __('Dismiss notification') }}">&times;</button>
        </div>
    </template>
</div>
