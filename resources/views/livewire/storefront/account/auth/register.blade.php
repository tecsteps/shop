<div class="flex min-h-screen items-center justify-center">
    <div class="w-full max-w-md space-y-6 p-8">
        <h1 class="text-center text-2xl font-bold">Create Account</h1>

        <form wire:submit="register" class="space-y-4">
            <flux:input wire:model="first_name" label="First Name" required />
            <flux:input wire:model="last_name" label="Last Name" required />
            <flux:input wire:model="email" label="Email" type="email" required />
            <flux:input wire:model="password" label="Password" type="password" required />
            <flux:input wire:model="password_confirmation" label="Confirm Password" type="password" required />

            @error('email')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <flux:button type="submit" variant="primary" class="w-full">
                Register
            </flux:button>

            <p class="text-center text-sm">
                Already have an account? <a href="{{ route('storefront.login') }}" class="underline">Log in</a>
            </p>
        </form>
    </div>
</div>
