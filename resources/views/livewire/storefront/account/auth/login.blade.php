<div class="flex min-h-screen items-center justify-center">
    <div class="w-full max-w-md space-y-6 p-8">
        <h1 class="text-center text-2xl font-bold">Customer Login</h1>

        <form wire:submit="login" class="space-y-4">
            <flux:input wire:model="email" label="Email" type="email" required />
            <flux:input wire:model="password" label="Password" type="password" required />

            @error('email')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <flux:button type="submit" variant="primary" class="w-full">
                Log in
            </flux:button>

            <p class="text-center text-sm">
                Don't have an account? <a href="{{ route('storefront.register') }}" class="underline">Register</a>
            </p>
        </form>
    </div>
</div>
