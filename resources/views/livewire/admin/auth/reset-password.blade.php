<flux:card>
    <div class="mb-6 text-center">
        <flux:heading size="lg">Reset password</flux:heading>
        <flux:text class="mt-1">Choose a new password for your account.</flux:text>
    </div>

    @if ($errorMessage)
        <flux:callout variant="danger" class="mb-4">
            <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
        </flux:callout>
    @endif

    <form wire:submit="resetPassword" class="space-y-4">
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
</flux:card>
