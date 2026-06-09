@php
    $socialIcons = [
        'facebook' => 'M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12Z',
        'instagram' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069Zm0 1.802c-3.15 0-3.504.011-4.747.068-2.412.11-3.524 1.24-3.635 3.636-.056 1.243-.067 1.596-.067 4.747s.011 3.504.067 4.748c.111 2.39 1.219 3.525 3.635 3.635 1.243.056 1.597.068 4.747.068 3.151 0 3.505-.012 4.748-.068 2.412-.11 3.524-1.24 3.635-3.635.056-1.244.067-1.597.067-4.748s-.011-3.504-.067-4.747c-.111-2.392-1.219-3.526-3.635-3.636-1.243-.057-1.597-.068-4.748-.068ZM12 7.054a4.946 4.946 0 1 1 0 9.892 4.946 4.946 0 0 1 0-9.892Zm0 1.802a3.144 3.144 0 1 0 0 6.288 3.144 3.144 0 0 0 0-6.288Zm5.106-3.034a1.156 1.156 0 1 1 0 2.312 1.156 1.156 0 0 1 0-2.312Z',
        'twitter' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231 5.45-6.231Zm-1.161 17.52h1.833L7.084 4.126H5.117l11.966 15.644Z',
        'tiktok' => 'M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1Z',
        'youtube' => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814ZM9.545 15.568V8.432L15.818 12l-6.273 3.568Z',
    ];
@endphp
<footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="grid grid-cols-2 gap-8 md:grid-cols-3">
            {{-- Footer menu links --}}
            <div>
                <h2 class="text-xs font-semibold tracking-wider text-zinc-900 uppercase dark:text-white">
                    {{ __('Shop') }}
                </h2>
                <ul class="mt-4 space-y-3">
                    <li>
                        <a href="{{ route('storefront.collections.index') }}" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                            {{ __('All collections') }}
                        </a>
                    </li>
                    @foreach ($mainMenu as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="text-xs font-semibold tracking-wider text-zinc-900 uppercase dark:text-white">
                    {{ __('Information') }}
                </h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($footerMenu as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @empty
                        <li>
                            <a href="{{ route('home') }}" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                {{ __('Home') }}
                            </a>
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- Store info --}}
            <div class="col-span-2 md:col-span-1">
                <h2 class="text-xs font-semibold tracking-wider text-zinc-900 uppercase dark:text-white">
                    {{ $storeName }}
                </h2>
                @if (filled($themeSettings['footer_text']))
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $themeSettings['footer_text'] }}</p>
                @endif
                @if (filled($themeSettings['social_links']))
                    <ul class="mt-4 flex items-center gap-4">
                        @foreach ($themeSettings['social_links'] as $platform => $link)
                            @if (filled($link))
                                <li>
                                    <a
                                        href="{{ $link }}"
                                        class="text-zinc-400 transition hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-200"
                                        rel="noopener noreferrer"
                                        target="_blank"
                                    >
                                        <span class="sr-only">{{ ucfirst($platform) }}</span>
                                        <svg class="size-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="{{ $socialIcons[$platform] ?? $socialIcons['facebook'] }}" />
                                        </svg>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-zinc-200 pt-8 sm:flex-row dark:border-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                &copy; {{ now()->year }} {{ $storeName }}. {{ __('All rights reserved.') }}
            </p>
            <ul class="flex items-center gap-2 opacity-75" aria-label="{{ __('Accepted payment methods') }}">
                @foreach (['Visa', 'Mastercard', 'Amex', 'PayPal'] as $paymentMethod)
                    <li class="rounded border border-zinc-300 bg-white px-2 py-1 text-[10px] font-semibold tracking-wide text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $paymentMethod }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</footer>
