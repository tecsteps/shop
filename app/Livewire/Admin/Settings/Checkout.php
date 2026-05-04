<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Checkout extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public bool $guestCheckoutEnabled = true;

    public bool $customerAccountsRequired = false;

    public bool $phoneNumberRequired = false;

    public bool $billingAddressEnabled = true;

    public bool $orderNotesEnabled = true;

    public bool $termsRequired = false;

    public string $termsUrl = '';

    public int $paymentHoldHours = 24;

    public int $abandonedCheckoutDays = 14;

    public int $bankTransferCancelDays = 7;

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('update', $store);

        $this->storeId = $store->getKey();
        $this->fillFromSettings($this->storeSettings($store)->settings_json ?? []);
    }

    public function save(): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $validated = $this->validate([
            'guestCheckoutEnabled' => ['boolean'],
            'customerAccountsRequired' => ['boolean'],
            'phoneNumberRequired' => ['boolean'],
            'billingAddressEnabled' => ['boolean'],
            'orderNotesEnabled' => ['boolean'],
            'termsRequired' => ['boolean'],
            'termsUrl' => ['nullable', 'url', 'max:255'],
            'paymentHoldHours' => ['required', 'integer', 'min:1', 'max:168'],
            'abandonedCheckoutDays' => ['required', 'integer', 'min:1', 'max:90'],
            'bankTransferCancelDays' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $settings = $this->storeSettings($store);
        $settings->forceFill([
            'settings_json' => array_replace_recursive($settings->settings_json ?? [], [
                'checkout' => [
                    'guest_checkout_enabled' => $validated['guestCheckoutEnabled'],
                    'customer_accounts_required' => $validated['customerAccountsRequired'],
                    'phone_number_required' => $validated['phoneNumberRequired'],
                    'billing_address_enabled' => $validated['billingAddressEnabled'],
                    'order_notes_enabled' => $validated['orderNotesEnabled'],
                    'terms_required' => $validated['termsRequired'],
                    'terms_url' => $validated['termsUrl'] ?? '',
                    'payment_hold_hours' => $validated['paymentHoldHours'],
                    'abandoned_checkout_days' => $validated['abandonedCheckoutDays'],
                ],
                'bank_transfer_cancel_days' => $validated['bankTransferCancelDays'],
            ]),
            'updated_at' => now(),
        ])->save();

        session()->flash('status', 'Checkout settings saved');
        $this->dispatch('toast', type: 'success', message: __('Checkout settings saved'));
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.checkout')
            ->layout('layouts.app', [
                'title' => __('Checkout settings'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function fillFromSettings(array $settings): void
    {
        $this->guestCheckoutEnabled = (bool) data_get($settings, 'checkout.guest_checkout_enabled', true);
        $this->customerAccountsRequired = (bool) data_get($settings, 'checkout.customer_accounts_required', false);
        $this->phoneNumberRequired = (bool) data_get($settings, 'checkout.phone_number_required', false);
        $this->billingAddressEnabled = (bool) data_get($settings, 'checkout.billing_address_enabled', true);
        $this->orderNotesEnabled = (bool) data_get($settings, 'checkout.order_notes_enabled', true);
        $this->termsRequired = (bool) data_get($settings, 'checkout.terms_required', false);
        $this->termsUrl = (string) data_get($settings, 'checkout.terms_url', '');
        $this->paymentHoldHours = (int) data_get($settings, 'checkout.payment_hold_hours', 24);
        $this->abandonedCheckoutDays = (int) data_get($settings, 'checkout.abandoned_checkout_days', 14);
        $this->bankTransferCancelDays = (int) data_get($settings, 'bank_transfer_cancel_days', 7);
    }

    private function store(): Store
    {
        $store = app('current_store');

        abort_unless($store instanceof Store, 404);

        return $store;
    }

    private function scopedStore(): Store
    {
        return Store::query()->whereKey($this->storeId)->firstOrFail();
    }

    private function storeSettings(Store $store): StoreSettings
    {
        return StoreSettings::query()->firstOrCreate(
            ['store_id' => $store->getKey()],
            ['settings_json' => []],
        );
    }
}
