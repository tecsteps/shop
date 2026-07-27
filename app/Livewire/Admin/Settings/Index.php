<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public string $tab = 'general';

    // General tab (spec 03 §11.1).
    public string $storeName = '';

    public string $contactEmail = '';

    public string $defaultCurrency = 'EUR';

    public string $defaultLocale = 'en';

    public string $timezone = 'UTC';

    // Domains tab (spec 03 §11.2).
    public bool $showDomainForm = false;

    public string $newHostname = '';

    public string $newType = 'storefront';

    // Checkout tab (spec 05 §10, §6).
    public string $orderNumberPrefix = '#';

    public ?int $orderNumberStart = 1001;

    public ?int $bankTransferCancelDays = 7;

    public ?int $cartAbandonDays = 14;

    // Notifications tab (cosmetic persistence in settings_json).
    public bool $orderConfirmationEmail = true;

    public bool $shippingEmail = true;

    public bool $marketingEmail = false;

    public function mount(): void
    {
        Gate::authorize('manage-store-settings');

        /** @var Store $store */
        $store = app('current_store');
        $settings = $store->settings?->settings_json ?? [];

        $this->storeName = $store->name;
        $this->defaultCurrency = $store->default_currency;
        $this->defaultLocale = $store->default_locale;
        $this->timezone = $store->timezone;

        $this->contactEmail = (string) ($settings['contact_email'] ?? '');
        $this->orderNumberPrefix = (string) ($settings['order_number_prefix'] ?? '#');
        $this->orderNumberStart = (int) ($settings['order_number_start'] ?? 1001);
        $this->bankTransferCancelDays = (int) ($settings['bank_transfer_cancel_days'] ?? 7);
        $this->cartAbandonDays = (int) ($settings['cart_abandon_days'] ?? 14);
        $this->orderConfirmationEmail = (bool) ($settings['notifications']['order_confirmation_email'] ?? true);
        $this->shippingEmail = (bool) ($settings['notifications']['shipping_email'] ?? true);
        $this->marketingEmail = (bool) ($settings['notifications']['marketing'] ?? false);
    }

    /**
     * Switch the visible settings tab (spec 03 §11.2).
     */
    public function setTab(string $tab): void
    {
        if (in_array($tab, ['general', 'domains', 'shipping', 'taxes', 'checkout', 'notifications'], true)) {
            $this->tab = $tab;
        }
    }

    /**
     * Save the general tab: store columns plus contact email in
     * store_settings.settings_json (spec 03 §11.1).
     */
    public function saveGeneral(): void
    {
        Gate::authorize('manage-store-settings');

        $validated = $this->validate([
            'storeName' => ['required', 'string', 'max:255'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'defaultCurrency' => ['required', 'string', 'size:3'],
            'defaultLocale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
        ]);

        /** @var Store $store */
        $store = app('current_store');

        DB::transaction(function () use ($store, $validated): void {
            $store->update([
                'name' => $validated['storeName'],
                'default_currency' => mb_strtoupper($validated['defaultCurrency']),
                'default_locale' => $validated['defaultLocale'],
                'timezone' => $validated['timezone'],
            ]);

            $this->mergeSettings($store, [
                'contact_email' => $validated['contactEmail'] ?: null,
                'store_name' => $validated['storeName'],
            ]);
        });

        $this->dispatch('toast', type: 'success', message: 'Settings saved');
    }

    /**
     * Add a domain to the store (spec 03 §11.2).
     */
    public function addDomain(): void
    {
        Gate::authorize('manage-store-settings');

        $validated = $this->validate([
            'newHostname' => [
                'required', 'string', 'max:253',
                'regex:/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i',
                Rule::unique('store_domains', 'hostname'),
            ],
            'newType' => ['required', Rule::in(['storefront', 'admin', 'api'])],
        ]);

        /** @var Store $store */
        $store = app('current_store');

        StoreDomain::create([
            'store_id' => $store->id,
            'hostname' => mb_strtolower($validated['newHostname']),
            'type' => $validated['newType'],
            'is_primary' => ! $store->domains()->exists(),
            'tls_mode' => 'managed',
        ]);

        $this->showDomainForm = false;
        $this->newHostname = '';
        $this->newType = 'storefront';

        $this->dispatch('toast', type: 'success', message: 'Domain added');
    }

    /**
     * Remove a domain. The primary domain cannot be removed — set another
     * primary first (spec 03 §11.2).
     */
    public function removeDomain(int $domainId): void
    {
        Gate::authorize('manage-store-settings');

        /** @var Store $store */
        $store = app('current_store');

        $domain = $store->domains()->find($domainId);

        if ($domain === null) {
            return;
        }

        if ($domain->is_primary) {
            $this->dispatch('toast', type: 'error', message: 'Set another domain as primary before removing this one.');

            return;
        }

        $domain->delete();

        $this->dispatch('toast', type: 'success', message: 'Domain removed');
    }

    /**
     * Mark a domain as the store's primary domain (spec 03 §11.2).
     */
    public function setPrimary(int $domainId): void
    {
        Gate::authorize('manage-store-settings');

        /** @var Store $store */
        $store = app('current_store');

        $domain = $store->domains()->find($domainId);

        if ($domain === null) {
            return;
        }

        DB::transaction(function () use ($store, $domain): void {
            $store->domains()->where('is_primary', true)->update(['is_primary' => false]);
            $domain->update(['is_primary' => true]);
        });

        $this->dispatch('toast', type: 'success', message: 'Primary domain updated');
    }

    /**
     * Save the checkout tab into store_settings.settings_json.
     */
    public function saveCheckout(): void
    {
        Gate::authorize('manage-store-settings');

        $validated = $this->validate([
            'orderNumberPrefix' => ['required', 'string', 'max:10'],
            'orderNumberStart' => ['required', 'integer', 'min:1'],
            'bankTransferCancelDays' => ['required', 'integer', 'min:1', 'max:90'],
            'cartAbandonDays' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        /** @var Store $store */
        $store = app('current_store');

        $this->mergeSettings($store, [
            'order_number_prefix' => $validated['orderNumberPrefix'],
            'order_number_start' => (int) $validated['orderNumberStart'],
            'bank_transfer_cancel_days' => (int) $validated['bankTransferCancelDays'],
            'cart_abandon_days' => (int) $validated['cartAbandonDays'],
        ]);

        $this->dispatch('toast', type: 'success', message: 'Settings saved');
    }

    /**
     * Save the notification toggles into store_settings.settings_json
     * (cosmetic persistence — no mail is wired up yet).
     */
    public function saveNotifications(): void
    {
        Gate::authorize('manage-store-settings');

        /** @var Store $store */
        $store = app('current_store');

        $this->mergeSettings($store, [
            'notifications' => [
                'order_confirmation_email' => $this->orderConfirmationEmail,
                'shipping_email' => $this->shippingEmail,
                'marketing' => $this->marketingEmail,
            ],
        ]);

        $this->dispatch('toast', type: 'success', message: 'Settings saved');
    }

    public function render(): View
    {
        /** @var Store $store */
        $store = app('current_store');

        return view('livewire.admin.settings.index', [
            'domains' => $store->domains()->orderByDesc('is_primary')->orderBy('id')->get(),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ])->layout('admin.layouts.app')->title('Settings');
    }

    /**
     * Merge keys into the store's settings bag, creating it when missing.
     *
     * @param  array<string, mixed>  $values
     */
    private function mergeSettings(Store $store, array $values): void
    {
        $settings = StoreSettings::firstOrNew(['store_id' => $store->id]);
        $settings->settings_json = array_merge($settings->settings_json ?? [], $values);
        $settings->save();
    }
}
