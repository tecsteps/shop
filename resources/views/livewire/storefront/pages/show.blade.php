<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[['label' => $title]]" />

    <article class="mt-8 space-y-5">
        <h1 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">{{ $title }}</h1>

        @if ($handle === 'about')
            <p class="text-lg text-zinc-600 dark:text-zinc-300">Acme Fashion is the demo storefront for this self-contained shop platform.</p>
            <p class="text-zinc-600 dark:text-zinc-400">The catalog includes multi-variant products, sale pricing, inventory rules, backorder handling, and digital product examples for checkout and storefront testing.</p>
        @else
            <p class="text-lg text-zinc-600 dark:text-zinc-300">Frequently asked questions for the demo storefront.</p>
            <p class="text-zinc-600 dark:text-zinc-400">Shipping, payment, returns, and account flows are implemented progressively according to the project roadmap.</p>
        @endif
    </article>
</section>
