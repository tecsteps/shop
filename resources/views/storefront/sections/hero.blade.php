<section
    class="relative flex min-h-[300px] items-center justify-center overflow-hidden sm:min-h-[400px] md:min-h-[500px] lg:min-h-[600px]"
    @if (filled($settings['hero_image_url']))
        style="background-image: url('{{ $settings['hero_image_url'] }}'); background-size: cover; background-position: center;"
    @else
        style="background-image: linear-gradient(135deg, var(--sf-primary, #1d4ed8), var(--sf-secondary, #3b82f6));"
    @endif
>
    <div class="absolute inset-0 bg-zinc-950/40" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 lg:px-8 lg:py-24">
        <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl md:text-5xl lg:text-6xl">
            {{ $settings['hero_heading'] }}
        </h1>
        @if (filled($settings['hero_subheading']))
            <p class="mx-auto mt-4 max-w-2xl text-base text-white/85 sm:mt-6 sm:text-lg md:text-xl">
                {{ $settings['hero_subheading'] }}
            </p>
        @endif
        @if (filled($settings['hero_cta_text']))
            <a
                href="{{ $settings['hero_cta_link'] ?: route('storefront.collections.index') }}"
                class="mt-8 inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-sm font-semibold text-zinc-900 shadow-lg transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-900 sm:mt-10 sm:px-8 sm:text-base"
            >
                {{ $settings['hero_cta_text'] }}
            </a>
        @endif
    </div>
</section>
