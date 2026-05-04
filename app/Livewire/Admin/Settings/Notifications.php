<?php

namespace App\Livewire\Admin\Settings;

use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Notifications extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $storeId;

    public string $senderName = '';

    public string $senderEmail = '';

    public string $replyToEmail = '';

    public bool $orderConfirmationEnabled = true;

    public bool $shippingConfirmationEnabled = true;

    public bool $refundConfirmationEnabled = true;

    public bool $adminOrderAlertsEnabled = true;

    public bool $lowStockAlertsEnabled = true;

    public int $lowStockThreshold = 5;

    public function mount(): void
    {
        $store = $this->store();

        $this->authorize('update', $store);

        $this->storeId = $store->getKey();
        $this->fillFromSettings($store, $this->storeSettings($store)->settings_json ?? []);
    }

    public function save(): void
    {
        $store = $this->scopedStore();

        $this->authorize('update', $store);

        $validated = $this->validate([
            'senderName' => ['required', 'string', 'max:255'],
            'senderEmail' => ['required', 'email', 'max:255'],
            'replyToEmail' => ['nullable', 'email', 'max:255'],
            'orderConfirmationEnabled' => ['boolean'],
            'shippingConfirmationEnabled' => ['boolean'],
            'refundConfirmationEnabled' => ['boolean'],
            'adminOrderAlertsEnabled' => ['boolean'],
            'lowStockAlertsEnabled' => ['boolean'],
            'lowStockThreshold' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $settings = $this->storeSettings($store);
        $settings->forceFill([
            'settings_json' => array_replace_recursive($settings->settings_json ?? [], [
                'notifications' => [
                    'sender_name' => $validated['senderName'],
                    'sender_email' => $validated['senderEmail'],
                    'reply_to_email' => $validated['replyToEmail'] ?? '',
                    'order_confirmation_enabled' => $validated['orderConfirmationEnabled'],
                    'shipping_confirmation_enabled' => $validated['shippingConfirmationEnabled'],
                    'refund_confirmation_enabled' => $validated['refundConfirmationEnabled'],
                    'admin_order_alerts_enabled' => $validated['adminOrderAlertsEnabled'],
                    'low_stock_alerts_enabled' => $validated['lowStockAlertsEnabled'],
                    'low_stock_threshold' => $validated['lowStockThreshold'],
                ],
            ]),
            'updated_at' => now(),
        ])->save();

        session()->flash('status', 'Notification settings saved');
        $this->dispatch('toast', type: 'success', message: __('Notification settings saved'));
    }

    public function render(): mixed
    {
        return view('livewire.admin.settings.notifications')
            ->layout('layouts.app', [
                'title' => __('Notification settings'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function fillFromSettings(Store $store, array $settings): void
    {
        $this->senderName = (string) data_get($settings, 'notifications.sender_name', $store->name);
        $this->senderEmail = (string) data_get($settings, 'notifications.sender_email', 'no-reply@shop.test');
        $this->replyToEmail = (string) data_get($settings, 'notifications.reply_to_email', '');
        $this->orderConfirmationEnabled = (bool) data_get($settings, 'notifications.order_confirmation_enabled', true);
        $this->shippingConfirmationEnabled = (bool) data_get($settings, 'notifications.shipping_confirmation_enabled', true);
        $this->refundConfirmationEnabled = (bool) data_get($settings, 'notifications.refund_confirmation_enabled', true);
        $this->adminOrderAlertsEnabled = (bool) data_get($settings, 'notifications.admin_order_alerts_enabled', true);
        $this->lowStockAlertsEnabled = (bool) data_get($settings, 'notifications.low_stock_alerts_enabled', true);
        $this->lowStockThreshold = (int) data_get($settings, 'notifications.low_stock_threshold', 5);
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
