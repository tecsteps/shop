<div>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Sign in</h1>
    <form wire:submit="login">
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" wire:model="email" />
            @error('email') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" wire:model="password" />
        </div>
        <div>
            <label>
                <input type="checkbox" wire:model="remember" /> Remember me
            </label>
        </div>
        <button type="submit">Sign in</button>
    </form>
</div>
