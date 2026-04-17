<div>
    <div class="flex flex-col gap-6">
        <div class="text-center">
            <flux:heading size="lg">Admin Login</flux:heading>
            <flux:text class="mt-2">Sign in to your admin account</flux:text>
        </div>

        <form wire:submit="login" class="flex flex-col gap-6">
            <flux:field>
                <flux:input wire:model="email" label="Email" type="email" placeholder="admin@example.com" required autofocus />
            </flux:field>

            @error('email')
                <flux:text class="text-red-500 text-sm">{{ $message }}</flux:text>
            @enderror

            <flux:field>
                <flux:input wire:model="password" label="Password" type="password" placeholder="Password" required />
            </flux:field>

            <div class="flex items-center gap-2">
                <flux:checkbox wire:model="remember" label="Remember me" />
            </div>

            <flux:button type="submit" variant="primary" class="w-full">
                Log in
            </flux:button>
        </form>
    </div>
</div>
