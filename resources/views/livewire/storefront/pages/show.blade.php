<div>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-storefront-breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('storefront.home')],
            ['label' => $this->page->title],
        ]" />

        <h1 class="mt-6 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl dark:text-white">
            {{ $this->page->title }}
        </h1>

        <div class="mt-8 max-w-3xl space-y-5 text-zinc-700 [&_a]:font-medium [&_a]:text-blue-600 [&_a]:underline [&_a]:underline-offset-2 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_h2]:text-zinc-900 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-zinc-900 [&_li]:mb-1.5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:leading-relaxed [&_ul]:list-disc [&_ul]:pl-5 dark:text-zinc-300 dark:[&_h2]:text-white dark:[&_h3]:text-white">
            {!! $this->page->body_html !!}
        </div>
    </div>
</div>
