<div>
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
        <button type="submit">Login</button>
    </form>
</div>
