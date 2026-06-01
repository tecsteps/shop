<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Notification settings (a tab within Settings). Persists transactional-email
 * preferences into the store settings bag under `notifications`. Owners/admins.
 */
class Notifications extends Component
{
    use BindsCurrentStore;

    public string $senderEmail = '';

    public bool $orderConfirmation = true;

    public bool $shippingConfirmation = true;

    public bool $refundNotification = true;

    public function mount(): void
    {
        $this->guard();

        $settings = app('current_store')->settings?->settings_json['notifications'] ?? [];
        $this->senderEmail = (string) ($settings['sender_email'] ?? '');
        $this->orderConfirmation = (bool) ($settings['order_confirmation'] ?? true);
        $this->shippingConfirmation = (bool) ($settings['shipping_confirmation'] ?? true);
        $this->refundNotification = (bool) ($settings['refund_notification'] ?? true);
    }

    private function guard(): void
    {
        if (! Gate::allows('manage-store-settings')) {
            abort(403);
        }
    }

    public function save(): void
    {
        $this->guard();

        $this->validate(['senderEmail' => ['nullable', 'email', 'max:255']]);

        $store = app('current_store');
        $existing = $store->settings?->settings_json ?? [];
        $existing['notifications'] = [
            'sender_email' => $this->senderEmail,
            'order_confirmation' => $this->orderConfirmation,
            'shipping_confirmation' => $this->shippingConfirmation,
            'refund_notification' => $this->refundNotification,
        ];

        StoreSettings::query()->updateOrCreate(['store_id' => $store->id], ['settings_json' => $existing]);

        $this->dispatch('toast', type: 'success', message: __('Settings saved'));
    }

    public function render()
    {
        return view('livewire.admin.settings.notifications');
    }
}
