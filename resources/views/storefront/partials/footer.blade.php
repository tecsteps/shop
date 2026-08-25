@php
    $footerText = $settings['footer_text'] ?? null;
    $address = $settings['store_address'] ?? null;
    $year = now()->year;
    $columns = max(2, min(4, (int) ($settings['footer_columns'] ?? 4)));
    $chunks = array_chunk($footerNav, max(1, (int) ceil(count($footerNav) / max(1, $columns - 1))));

    $socials = [
        'facebook' => ['url' => $settings['social_facebook'] ?? null, 'label' => 'Facebook'],
        'instagram' => ['url' => $settings['social_instagram'] ?? null, 'label' => 'Instagram'],
        'twitter' => ['url' => $settings['social_twitter'] ?? null, 'label' => 'Twitter'],
        'tiktok' => ['url' => $settings['social_tiktok'] ?? null, 'label' => 'TikTok'],
        'youtube' => ['url' => $settings['social_youtube'] ?? null, 'label' => 'YouTube'],
    ];
@endphp

<footer class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-8 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($chunks as $chunk)
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Links</h2>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($chunk as $item)
                            <li>
                                <a href="{{ $item['url'] }}" class="text-sm text-zinc-600 transition hover:text-zinc-900 dark:text-zinc-300 dark:hover:text-white">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $storeName }}</h2>
                <address class="mt-4 space-y-2.5 text-sm not-italic text-zinc-600 dark:text-zinc-300">
                    @if ($address)
                        <p>{{ $address }}</p>
                    @endif
                    @if ($contactEmail)
                        <p>
                            <a href="mailto:{{ $contactEmail }}" class="transition hover:text-zinc-900 dark:hover:text-white">{{ $contactEmail }}</a>
                        </p>
                    @endif
                </address>
            </div>
        </div>

        @if (collect($socials)->filter(fn ($social) => $social['url'])->isNotEmpty())
            <div class="mt-10 flex items-center gap-4">
                @foreach ($socials as $key => $social)
                    @if ($social['url'])
                        <a
                            href="{{ $social['url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ $social['label'] }} (opens in a new tab)"
                            class="text-zinc-400 transition hover:text-zinc-900 dark:text-zinc-500 dark:hover:text-white"
                        >
                            @include('storefront.partials.social-icon', ['key' => $key])
                        </a>
                    @endif
                @endforeach
            </div>
        @endif

        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-zinc-200 pt-6 sm:flex-row dark:border-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                &copy; {{ $year }} {{ $storeName }}. All rights reserved.
            </p>

            @if (($settings['payment_icons'] ?? true) !== false)
                <div class="flex items-center gap-2 opacity-70" aria-hidden="true">
                    <span class="rounded border border-zinc-200 bg-white px-1.5 py-0.5 text-[10px] font-bold text-blue-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-blue-400">VISA</span>
                    <span class="rounded border border-zinc-200 bg-white px-1.5 py-0.5 text-[10px] font-bold italic text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">Mastercard</span>
                    <span class="rounded border border-zinc-200 bg-white px-1.5 py-0.5 text-[10px] font-bold text-sky-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-sky-400">AMEX</span>
                    <span class="rounded border border-zinc-200 bg-white px-1.5 py-0.5 text-[10px] font-bold text-sky-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-sky-500">PayPal</span>
                </div>
            @endif
        </div>

        @if ($store)
            @php($organizationJson = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $storeName,
                'url' => url('/'),
                ...($contactEmail ? ['email' => 'mailto:'.$contactEmail] : []),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            <script type="application/ld+json">{!! $organizationJson !!}</script>
        @endif
    </div>
</footer>
