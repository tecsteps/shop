<div>
    @php
        $activeSettingsTab = match (request()->query('tab')) {
            'domains' => 'Domains',
            'checkout' => 'Checkout',
            'notifications' => 'Notifications',
            default => 'General',
        };

        $tabDescriptions = [
            'General' => 'Manage your store identity and regional defaults.',
            'Domains' => 'Manage storefront, admin, and API hostnames for this store.',
            'Checkout' => 'Review the settings that shape your checkout experience.',
            'Notifications' => 'Review how store notifications are handled.',
        ];
    @endphp

    @include('livewire.admin.components.page-heading', [
        'title' => $activeSettingsTab === 'General' ? 'General settings' : $activeSettingsTab,
        'description' => $tabDescriptions[$activeSettingsTab],
        'breadcrumbs' => [['label' => 'Settings']],
    ])
    @include('livewire.admin.components.settings-nav', ['active' => $activeSettingsTab])

    @if ($activeSettingsTab === 'Domains')
        <livewire:admin.settings.domains />
    @elseif ($activeSettingsTab === 'Checkout')
        <section aria-labelledby="checkout-settings-heading" class="max-w-4xl rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 id="checkout-settings-heading" class="text-lg font-semibold">Checkout settings</h2>
            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">Checkout uses this store’s regional defaults, shipping rates, and tax rules.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('admin.settings.shipping') }}" wire:navigate class="inline-flex min-h-10 items-center rounded-xl border border-zinc-300 px-4 text-sm font-medium text-zinc-800 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-800">Manage shipping</a>
                <a href="{{ route('admin.settings.taxes') }}" wire:navigate class="inline-flex min-h-10 items-center rounded-xl border border-zinc-300 px-4 text-sm font-medium text-zinc-800 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-800">Manage taxes</a>
            </div>
        </section>
    @elseif ($activeSettingsTab === 'Notifications')
        <section aria-labelledby="notification-settings-heading" class="max-w-4xl rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 id="notification-settings-heading" class="text-lg font-semibold">Notification settings</h2>
            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">No additional notification preferences are available for this store yet. Order confirmations, refunds, cancellations, and shipment updates are sent to the email address on each order.</p>
            <a href="{{ route('admin.settings') }}" wire:navigate class="mt-4 inline-flex min-h-10 items-center rounded-xl border border-zinc-300 px-4 text-sm font-medium text-zinc-800 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-800">Review general settings</a>
        </section>
    @else
        @if (session('status'))
            <p role="status" class="mb-5 max-w-4xl rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">{{ session('status') }}</p>
        @endif

        <form wire:submit="save" class="max-w-5xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <section aria-labelledby="store-details-heading" class="grid gap-6 border-b border-zinc-200 p-5 sm:p-6 md:grid-cols-[minmax(12rem,.7fr)_minmax(0,1.3fr)] dark:border-zinc-800">
                <div>
                    <h2 id="store-details-heading" class="font-semibold">Store details</h2>
                    <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">Basic information about your store.</p>
                </div>
                <div class="grid gap-5">
                    <div>
                        <label for="settings-store-name" class="mb-1.5 block text-sm font-medium">Store name</label>
                        <input id="settings-store-name" type="text" wire:model="storeName" required autocomplete="organization" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('storeName') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="settings-store-handle" class="mb-1.5 block text-sm font-medium">Store handle</label>
                        <input id="settings-store-handle" type="text" value="{{ $storeHandle }}" disabled aria-describedby="settings-store-handle-help" class="min-h-11 w-full cursor-not-allowed rounded-xl border border-zinc-300 bg-zinc-100 px-3.5 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        <p id="settings-store-handle-help" class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">The store handle cannot be changed after creation.</p>
                    </div>
                    <div>
                        <label for="settings-contact-email" class="mb-1.5 block text-sm font-medium">Contact email</label>
                        <input id="settings-contact-email" type="email" autocomplete="email" wire:model="contactEmail" required class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('contactEmail') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section aria-labelledby="store-defaults-heading" class="grid gap-6 p-5 sm:p-6 md:grid-cols-[minmax(12rem,.7fr)_minmax(0,1.3fr)]">
                <div>
                    <h2 id="store-defaults-heading" class="font-semibold">Defaults</h2>
                    <p class="mt-1 text-sm leading-6 text-zinc-500 dark:text-zinc-400">Currency, language, and timezone settings.</p>
                </div>
                <div class="grid gap-5">
                    <div>
                        <label for="settings-currency" class="mb-1.5 block text-sm font-medium">Default currency</label>
                        <select id="settings-currency" wire:model="defaultCurrency" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="EUR">EUR · Euro</option>
                            <option value="USD">USD · US dollar</option>
                            <option value="GBP">GBP · Pound sterling</option>
                            <option value="CAD">CAD · Canadian dollar</option>
                            <option value="AUD">AUD · Australian dollar</option>
                        </select>
                        @error('defaultCurrency') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="settings-locale" class="mb-1.5 block text-sm font-medium">Default language</label>
                        <select id="settings-locale" wire:model="defaultLocale" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach ($locales as $localeCode => $localeName)
                                <option value="{{ $localeCode }}">{{ $localeName }}</option>
                            @endforeach
                        </select>
                        @error('defaultLocale') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="settings-timezone" class="mb-1.5 block text-sm font-medium">Timezone</label>
                        <select id="settings-timezone" wire:model="timezone" class="min-h-11 w-full rounded-xl border border-zinc-300 bg-white px-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach ($timezones as $timezoneOption)
                                <option value="{{ $timezoneOption }}">{{ $timezoneOption }}</option>
                            @endforeach
                        </select>
                        @error('timezone') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <div class="flex justify-end border-t border-zinc-200 px-5 py-4 sm:px-6 dark:border-zinc-800">
                <button type="submit" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">Save settings</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
            </div>
        </form>
    @endif
</div>
