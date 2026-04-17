<x-layouts::storefront>
    <x-slot:title>My Account</x-slot:title>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold">My Account</h1>

        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('customer.orders') }}" class="group rounded-lg border border-gray-200 p-6 hover:border-blue-500 dark:border-gray-700 dark:hover:border-blue-500">
                <h2 class="text-lg font-semibold group-hover:text-blue-600">Orders</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">View your order history</p>
            </a>

            <a href="{{ route('customer.addresses') }}" class="group rounded-lg border border-gray-200 p-6 hover:border-blue-500 dark:border-gray-700 dark:hover:border-blue-500">
                <h2 class="text-lg font-semibold group-hover:text-blue-600">Addresses</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage your saved addresses</p>
            </a>

            <form action="{{ route('customer.logout') }}" method="POST" class="group rounded-lg border border-gray-200 p-6 hover:border-red-500 dark:border-gray-700 dark:hover:border-red-500">
                @csrf
                <button type="submit" class="w-full text-left">
                    <h2 class="text-lg font-semibold group-hover:text-red-600">Log Out</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Sign out of your account</p>
                </button>
            </form>
        </div>
    </div>
</x-layouts::storefront>
