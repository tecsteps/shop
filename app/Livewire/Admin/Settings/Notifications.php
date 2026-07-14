<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\StoreSettings;
use Illuminate\View\View;

final class Notifications extends AdminComponent
{
    public bool $orderConfirmation = true;

    public bool $shippingConfirmation = true;

    public bool $refundConfirmation = true;

    public bool $cancellationConfirmation = true;

    public bool $notifyStaffOfNewOrders = true;

    public function mount(): void
    {
        $this->authorizeSettings();
        $settings = (array) StoreSettings::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->value('settings_json');
        $this->orderConfirmation = (bool) data_get($settings, 'notifications.order_confirmation', true);
        $this->shippingConfirmation = (bool) data_get($settings, 'notifications.shipping_confirmation', true);
        $this->refundConfirmation = (bool) data_get($settings, 'notifications.refund_confirmation', true);
        $this->cancellationConfirmation = (bool) data_get($settings, 'notifications.cancellation_confirmation', true);
        $this->notifyStaffOfNewOrders = (bool) data_get($settings, 'notifications.staff_new_order', true);
    }

    public function save(): void
    {
        $this->authorizeSettings();
        $data = $this->validate([
            'orderConfirmation' => ['boolean'],
            'shippingConfirmation' => ['boolean'],
            'refundConfirmation' => ['boolean'],
            'cancellationConfirmation' => ['boolean'],
            'notifyStaffOfNewOrders' => ['boolean'],
        ]);
        $record = StoreSettings::withoutGlobalScopes()->firstOrCreate(['store_id' => $this->currentStore()->id], ['settings_json' => []]);
        $settings = (array) $record->settings_json;
        data_set($settings, 'notifications', [
            'order_confirmation' => $data['orderConfirmation'],
            'shipping_confirmation' => $data['shippingConfirmation'],
            'refund_confirmation' => $data['refundConfirmation'],
            'cancellation_confirmation' => $data['cancellationConfirmation'],
            'staff_new_order' => $data['notifyStaffOfNewOrders'],
        ]);
        $record->update(['settings_json' => $settings]);
        $this->toast('Notification settings saved');
    }

    public function render(): View
    {
        return $this->admin(view('admin.settings.notifications'), 'Notification Settings', [['label' => 'Settings', 'url' => url('/admin/settings')], ['label' => 'Notifications']]);
    }

    private function authorizeSettings(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }
}
