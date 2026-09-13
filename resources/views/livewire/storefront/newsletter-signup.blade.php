<div class="bg-zinc-100 py-12 dark:bg-zinc-900">
    <div class="mx-auto max-w-xl px-4 text-center">
        <h2 class="text-2xl font-bold">Stay in the loop</h2>
        <p class="mt-1 text-zinc-600 dark:text-zinc-400">Subscribe for exclusive offers and updates.</p>
        @if ($subscribed)
            <p class="mt-4 font-medium" role="status">Thanks for subscribing!</p>
        @else
            <form wire:submit="subscribe" class="mt-4 flex gap-2">
                <flux:input wire:model="email" type="email" placeholder="Enter your email" aria-label="Email address" required class="flex-1" />
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                    <span wire:loading wire:target="subscribe">Subscribing...</span>
                </flux:button>
            </form>
            @error('email') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
        @endif
    </div>
</div>
