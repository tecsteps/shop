@if (filled($settings['rich_text_html']))
    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:py-16">
        <div class="sf-prose">
            {!! $settings['rich_text_html'] !!}
        </div>
    </section>
@endif
