<div class="flex min-h-screen items-center justify-center">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow dark:bg-gray-800">
        <h1 class="mb-6 text-2xl font-bold dark:text-white">Customer Login</h1>

        <form wire:submit="authenticate">
            <div class="mb-4">
                <label for="email" class="mb-1 block text-sm font-medium dark:text-gray-200">Email</label>
                <input wire:model="email" type="email" id="email" class="w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                @error('email') <span class="mt-1 text-sm text-red-500">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="mb-1 block text-sm font-medium dark:text-gray-200">Password</label>
                <input wire:model="password" type="password" id="password" class="w-full rounded border px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
            </div>

            <div class="mb-4">
                <label class="flex items-center">
                    <input wire:model="remember" type="checkbox" class="mr-2 rounded">
                    <span class="text-sm dark:text-gray-200">Remember me</span>
                </label>
            </div>

            <button type="submit" class="w-full rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                Log in
            </button>
        </form>

        <p class="mt-4 text-center text-sm dark:text-gray-300">
            Don't have an account? <a href="/account/register" class="text-blue-600 hover:underline">Register</a>
        </p>
    </div>
</div>
