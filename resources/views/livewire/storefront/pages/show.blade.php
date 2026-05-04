<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
    <x-storefront.breadcrumbs :items="[['label' => $title]]" />

    <article class="mt-8 space-y-5">
        <h1 class="text-3xl font-semibold tracking-normal text-zinc-950 dark:text-white">{{ $title }}</h1>

        <div class="space-y-4 text-zinc-600 dark:text-zinc-300">
            {!! $bodyHtml !!}
        </div>
    </article>
</section>
