<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminComponent;
use App\Models\StoreSettings;
use Illuminate\View\View;

final class Checkout extends AdminComponent
{
    public bool $allowGuestCheckout = true;

    public bool $requirePhone = false;

    public bool $requireCompany = false;

    public int $checkoutExpiryHours = 24;

    public function mount(): void
    {
        $this->authorizeSettings();
        $settings = (array) StoreSettings::withoutGlobalScopes()->where('store_id', $this->currentStore()->id)->value('settings_json');
        $this->allowGuestCheckout = (bool) data_get($settings, 'checkout.allow_guest_checkout', true);
        $this->requirePhone = (bool) data_get($settings, 'checkout.require_phone', false);
        $this->requireCompany = (bool) data_get($settings, 'checkout.require_company', false);
        $this->checkoutExpiryHours = (int) data_get($settings, 'checkout.expiry_hours', 24);
    }

    public function save(): void
    {
        $this->authorizeSettings();
        $data = $this->validate([
            'allowGuestCheckout' => ['boolean'],
            'requirePhone' => ['boolean'],
            'requireCompany' => ['boolean'],
            'checkoutExpiryHours' => ['required', 'integer', 'min:1', 'max:168'],
        ]);
        $record = StoreSettings::withoutGlobalScopes()->firstOrCreate(['store_id' => $this->currentStore()->id], ['settings_json' => []]);
        $settings = (array) $record->settings_json;
        data_set($settings, 'checkout', [
            'allow_guest_checkout' => $data['allowGuestCheckout'],
            'require_phone' => $data['requirePhone'],
            'require_company' => $data['requireCompany'],
            'expiry_hours' => $data['checkoutExpiryHours'],
        ]);
        $record->update(['settings_json' => $settings]);
        $this->toast('Checkout settings saved');
    }

    public function render(): View
    {
        return $this->admin(view('admin.settings.checkout'), 'Checkout Settings', [['label' => 'Settings', 'url' => url('/admin/settings')], ['label' => 'Checkout']]);
    }

    private function authorizeSettings(): void
    {
        $this->requireRoles(['owner', 'admin']);
        $this->authorizeAction('update', $this->currentStore());
    }
}
