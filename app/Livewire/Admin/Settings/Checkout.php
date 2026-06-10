<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Checkout settings tab: guest checkout toggle and the unpaid bank transfer
 * auto-cancel window (consumed by CancelUnpaidBankTransferOrders).
 */
class Checkout extends Component
{
    use AuthorizesRequests, SendsToasts;

    public bool $guestCheckoutEnabled = true;

    public int $bankTransferCancelDays = 7;

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('viewSettings', $store);

        $settings = $store->settings?->settings_json ?? [];

        $this->guestCheckoutEnabled = (bool) ($settings['guest_checkout_enabled'] ?? true);
        $this->bankTransferCancelDays = (int) ($settings['bank_transfer_cancel_days'] ?? 7);
    }

    public function save(): void
    {
        $store = $this->store();

        $this->authorize('updateSettings', $store);

        $this->validate([
            'bankTransferCancelDays' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $settings = StoreSettings::query()->firstOrNew(['store_id' => $store->getKey()]);

        $settings->settings_json = array_merge($settings->settings_json ?? [], [
            'guest_checkout_enabled' => $this->guestCheckoutEnabled,
            'bank_transfer_cancel_days' => $this->bankTransferCancelDays,
        ]);

        $settings->save();

        $this->toast(__('Settings saved'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.checkout');
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
