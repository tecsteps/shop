<div class="mx-auto max-w-md px-4 py-16 sm:px-6">
    <h1 class="text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Reset password</h1>

    @if ($errorMessage)
        <flux:callout variant="danger" class="mt-6">
            <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="resetPassword" class="mt-8 space-y-5">
        <flux:field>
            <flux:label for="email">Email</flux:label>
            <flux:input id="email" type="email" wire:model="email" autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label for="password">New password</flux:label>
            <flux:input id="password" type="password" wire:model="password" autocomplete="new-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label for="password_confirmation">Confirm password</flux:label>
            <flux:input id="password_confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="w-full">Reset password</flux:button>
    </form>
</div>
