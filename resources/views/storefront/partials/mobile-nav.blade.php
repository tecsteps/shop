@php
    $accountUrl = $customer ? route('account.dashboard') : route('account.login');
    $accountLabel = $customer ? 'Your account' : 'Log in';
@endphp

{{-- Mobile navigation drawer (Alpine state lives on the header) --}}
<div
    x-show="mobileOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    class="fixed inset-0 z-50 lg:hidden"
    role="dialog"
    aria-modal="true"
    aria-label="Mobile navigation"
>
    <div class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm" @click="mobileOpen = false" aria-hidden="true"></div>

    <div class="absolute inset-y-0 left-0 flex w-full max-w-xs flex-col bg-white shadow-2xl dark:bg-zinc-950">
        <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <span class="text-base font-bold text-zinc-900 dark:text-white">{{ $storeName }}</span>
            <button
                type="button"
                @click="mobileOpen = false"
                aria-label="Close navigation"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800 dark:hover:text-white"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav aria-label="Mobile navigation" class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-1">
                @foreach ($mainNav as $item)
                    <li>
                        <a
                            href="{{ $item['url'] }}"
                            @click="mobileOpen = false"
                            class="block rounded-lg px-3 py-3 text-base font-medium text-zinc-800 transition hover:bg-zinc-100 dark:text-zinc-100 dark:hover:bg-zinc-800"
                        >
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            <a
                href="{{ $accountUrl }}"
                @click="mobileOpen = false"
                class="flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                {{ $accountLabel }}
            </a>
        </div>
    </div>
</div>
