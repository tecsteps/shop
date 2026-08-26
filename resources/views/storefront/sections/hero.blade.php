@php
    $heading = $hero['heading'] ?? null;
    $subheading = $hero['subheading'] ?? null;
    $ctaText = $hero['ctaText'] ?? null;
    $ctaLink = $hero['ctaLink'] ?? null;
    $image = $hero['image'] ?? null;
@endphp

@if ($heading || $subheading)
    <section class="relative flex min-h-[300px] items-center overflow-hidden bg-zinc-900 sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px]" aria-labelledby="hero-heading">
        @if ($image)
            <img src="{{ $image }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center" />
            <div class="absolute inset-0 bg-zinc-950/55" aria-hidden="true"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900" aria-hidden="true"></div>
        @endif

        <div class="relative mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                @if ($heading)
                    <h1 id="hero-heading" class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                        {{ $heading }}
                    </h1>
                @endif

                @if ($subheading)
                    <p class="mt-5 max-w-xl text-base text-zinc-200 sm:text-lg">{{ $subheading }}</p>
                @endif

                @if ($ctaText && $ctaLink)
                    <div class="mt-8">
                        <a
                            href="{{ $ctaLink }}"
                            class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-6 py-3.5 text-base font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-zinc-900 dark:bg-blue-500 dark:hover:bg-blue-400"
                        >
                            {{ $ctaText }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
