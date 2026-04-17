<div class="flex flex-col gap-6">
    <flux:heading size="xl">Forgot your password?</flux:heading>

    @if ($status)
        <div class="rounded border border-neutral-200 bg-neutral-50 p-3 text-sm text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
            {{ $status }}
        </div>
    @endif

    <form wire:submit="sendLink" class="flex flex-col gap-4">
        <flux:input wire:model="email" type="email" label="Email" required />
        <flux:button type="submit" variant="primary">Email password reset link</flux:button>
    </form>

    <div class="text-sm">
        <a href="{{ url('/account/login') }}" class="text-neutral-700 underline dark:text-neutral-300">Back to sign in</a>
    </div>
</div>
