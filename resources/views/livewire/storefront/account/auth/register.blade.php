<div>
    <form wire:submit="register">
        <div>
            <label for="name">Name</label>
            <input type="text" id="name" wire:model="name" />
            @error('name') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" wire:model="email" />
            @error('email') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" wire:model="password" />
            @error('password') <span>{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" wire:model="password_confirmation" />
        </div>
        <div>
            <label>
                <input type="checkbox" wire:model="marketing_opt_in" /> Subscribe to marketing
            </label>
        </div>
        <button type="submit">Register</button>
    </form>
</div>
