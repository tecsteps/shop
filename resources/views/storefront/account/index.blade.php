<x-layouts::storefront :title="__('My account')">
    <div class="flex flex-col gap-4">
        <h1 class="text-2xl font-semibold">{{ __('My account') }}</h1>
        <p class="text-zinc-600 dark:text-zinc-400">
            {{ __('Hello :name', ['name' => auth('customer')->user()?->name]) }}
        </p>

        <form method="POST" action="{{ route('storefront.account.logout') }}">
            @csrf
            <flux:button type="submit" variant="ghost" data-test="customer-logout-button">
                {{ __('Log out') }}
            </flux:button>
        </form>
    </div>
</x-layouts::storefront>
