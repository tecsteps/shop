@php
    $html = $settings['rich_text_html'] ?? null;
@endphp

@if ($html !== null && trim($html) !== '')
    <section class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="space-y-4 text-zinc-700 [&_a]:font-medium [&_a]:text-blue-600 [&_a]:underline [&_a]:underline-offset-2 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_h2]:text-zinc-900 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-zinc-900 [&_li]:mb-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:leading-relaxed [&_ul]:list-disc [&_ul]:pl-5 dark:text-zinc-300 dark:[&_h2]:text-white dark:[&_h3]:text-white">
            {!! $html !!}
        </div>
    </section>
@endif
