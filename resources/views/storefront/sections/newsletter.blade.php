<section class="bg-zinc-50 dark:bg-zinc-900" aria-labelledby="newsletter-heading">
    <div class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <h2 id="newsletter-heading" class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
            Stay in the loop
        </h2>
        <p class="mt-3 text-zinc-600 dark:text-zinc-300">
            Subscribe for exclusive offers and updates.
        </p>

        @if ($this->newsletterMessage)
            <p class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300" role="status">
                {{ $this->newsletterMessage }}
            </p>
        @else
            <form
                class="mx-auto mt-6 flex max-w-md flex-col gap-3 sm:flex-row"
                wire:submit="subscribe"
                novalidate
            >
                <label for="newsletter-email" class="sr-only">Email address</label>
                <input
                    id="newsletter-email"
                    type="email"
                    wire:model="newsletterEmail"
                    placeholder="Enter your email"
                    autocomplete="email"
                    required
                    class="block w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 placeholder-zinc-400 transition focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:placeholder-zinc-500 dark:focus:border-white dark:focus:ring-white/20"
                    aria-describedby="newsletter-error"
                />
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="shrink-0 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 dark:bg-blue-500 dark:hover:bg-blue-400"
                >
                    <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                    <span wire:loading wire:target="subscribe">Subscribing...</span>
                </button>
            </form>
            @error('newsletterEmail')
                <p id="newsletter-error" class="mt-3 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
        @endif
    </div>
</section>
