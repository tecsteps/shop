<div>
    <flux:button wire:click="add" variant="ghost" size="sm" wire:loading.attr="disabled" class="mt-1 px-0">
        <span wire:loading.remove wire:target="add">Add to cart</span>
        <span wire:loading wire:target="add">Adding...</span>
    </flux:button>
    @if ($message)
        <p class="text-xs text-amber-600" aria-live="polite">{{ $message }}</p>
    @endif
</div>
