<div class="flex min-h-svh items-center justify-center p-6">
    <div class="w-full max-w-sm">
        <div class="flex flex-col gap-6">
            <div class="text-center">
                <flux:heading size="lg">Create Account</flux:heading>
                <flux:text class="mt-2">Register for a new account</flux:text>
            </div>

            <form wire:submit="register" class="flex flex-col gap-6">
                <flux:field>
                    <flux:input wire:model="name" label="Name" type="text" required autofocus />
                    @error('name')
                        <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:input wire:model="email" label="Email" type="email" placeholder="you@example.com" required />
                    @error('email')
                        <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:input wire:model="password" label="Password" type="password" required />
                    @error('password')
                        <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:input wire:model="password_confirmation" label="Confirm Password" type="password" required />
                </flux:field>

                <flux:button type="submit" variant="primary" class="w-full">
                    Register
                </flux:button>
            </form>

            <div class="text-center">
                <flux:text>
                    Already have an account?
                    <a href="{{ route('storefront.account.login') }}" class="underline" wire:navigate>Log in</a>
                </flux:text>
            </div>
        </div>
    </div>
</div>
