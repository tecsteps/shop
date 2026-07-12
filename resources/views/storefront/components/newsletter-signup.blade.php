<section class="sf-section bg-blue-950 text-white" aria-labelledby="newsletter-heading">
    <div class="sf-container max-w-3xl text-center">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-200">A note from us</p>
        <h2 id="newsletter-heading" class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Stay in the loop</h2>
        <p class="mx-auto mt-3 max-w-xl text-blue-100">Subscribe for thoughtful product updates, useful stories, and occasional offers.</p>

        <div class="mx-auto mt-7 max-w-xl" aria-live="polite">
            @if ($subscribed)
                <div class="rounded-xl border border-emerald-300/30 bg-emerald-400/10 p-4 font-medium text-emerald-100">Thanks for subscribing!</div>
            @else
                <form wire:submit="subscribe" class="flex flex-col gap-3 sm:flex-row">
                    <label for="newsletter-email" class="sr-only">Email address</label>
                    <input id="newsletter-email" name="newsletter_email" type="email" autocomplete="email" required wire:model="email" placeholder="Enter your email" class="sf-input min-h-12 flex-1 border-white/20 bg-white text-slate-950 placeholder:text-slate-500">
                    <button type="submit" class="sf-button min-h-12 bg-white px-6 text-blue-950 hover:bg-blue-50" wire:loading.attr="disabled" wire:target="subscribe">
                        <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                        <span wire:loading wire:target="subscribe">Subscribing...</span>
                    </button>
                </form>
                @error('email') <p class="mt-2 text-left text-sm text-red-200">{{ $message }}</p> @enderror
            @endif
        </div>
    </div>
</section>
