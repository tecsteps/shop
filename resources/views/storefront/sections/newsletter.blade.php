@if ($settings['show_newsletter'])
    <section class="bg-zinc-50 dark:bg-zinc-900" aria-labelledby="newsletter-heading">
        <div class="mx-auto max-w-2xl px-4 py-14 text-center sm:px-6 lg:py-20">
            <h2 id="newsletter-heading" class="text-xl font-bold tracking-tight text-zinc-900 sm:text-2xl dark:text-white">
                {{ __('Stay in the loop') }}
            </h2>
            <p class="mt-2 text-sm text-zinc-600 sm:text-base dark:text-zinc-400">
                {{ __('Subscribe for exclusive offers and updates.') }}
            </p>
            {{-- Newsletter subscriptions are stored from Phase 8 onward; this form confirms client-side for now. --}}
            <div x-data="{ subscribed: false }" class="mt-6">
                <form x-show="! subscribed" x-on:submit.prevent="subscribed = true" class="flex flex-col gap-3 sm:flex-row">
                    <label for="newsletter-email" class="sr-only">{{ __('Email address') }}</label>
                    <input
                        id="newsletter-email"
                        type="email"
                        required
                        placeholder="{{ __('Enter your email') }}"
                        class="block w-full rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500"
                    />
                    <button
                        type="submit"
                        class="shrink-0 rounded-lg bg-(--sf-primary,#1d4ed8) px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
                    >
                        {{ __('Subscribe') }}
                    </button>
                </form>
                <p x-show="subscribed" x-cloak class="text-sm font-medium text-green-700 dark:text-green-400" role="status" aria-live="polite">
                    {{ __('Thanks for subscribing!') }}
                </p>
            </div>
        </div>
    </section>
@endif
