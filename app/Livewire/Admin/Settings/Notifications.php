<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\SendsToasts;
use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Notifications settings tab: customer-facing transactional email toggles
 * and the internal notification recipient.
 */
class Notifications extends Component
{
    use AuthorizesRequests, SendsToasts;

    public string $notificationEmail = '';

    public bool $sendOrderConfirmation = true;

    public bool $sendShippingConfirmation = true;

    public bool $notifyOnNewOrder = true;

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('viewSettings', $store);

        $settings = $store->settings?->settings_json ?? [];

        $this->notificationEmail = (string) ($settings['notification_email'] ?? $settings['contact_email'] ?? '');
        $this->sendOrderConfirmation = (bool) ($settings['send_order_confirmation'] ?? true);
        $this->sendShippingConfirmation = (bool) ($settings['send_shipping_confirmation'] ?? true);
        $this->notifyOnNewOrder = (bool) ($settings['notify_on_new_order'] ?? true);
    }

    public function save(): void
    {
        $store = $this->store();

        $this->authorize('updateSettings', $store);

        $this->validate([
            'notificationEmail' => ['nullable', 'email', 'max:255'],
        ]);

        $settings = StoreSettings::query()->firstOrNew(['store_id' => $store->getKey()]);

        $settings->settings_json = array_merge($settings->settings_json ?? [], [
            'notification_email' => $this->notificationEmail !== '' ? $this->notificationEmail : null,
            'send_order_confirmation' => $this->sendOrderConfirmation,
            'send_shipping_confirmation' => $this->sendShippingConfirmation,
            'notify_on_new_order' => $this->notifyOnNewOrder,
        ]);

        $settings->save();

        $this->toast(__('Settings saved'));
    }

    public function render(): View
    {
        return view('livewire.admin.settings.notifications');
    }

    protected function store(): Store
    {
        return app('current_store');
    }
}
