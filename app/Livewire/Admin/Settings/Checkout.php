<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\Concerns\BindsCurrentStore;
use App\Models\StoreSettings;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Checkout settings (a tab within Settings). Persists checkout preferences into
 * the store settings bag under the `checkout` key. Owners and admins only.
 */
class Checkout extends Component
{
    use BindsCurrentStore;

    public bool $guestCheckoutEnabled = true;

    public bool $requirePhone = false;

    public string $termsUrl = '';

    public function mount(): void
    {
        $this->guard();

        $settings = app('current_store')->settings?->settings_json['checkout'] ?? [];
        $this->guestCheckoutEnabled = (bool) ($settings['guest_checkout_enabled'] ?? true);
        $this->requirePhone = (bool) ($settings['require_phone'] ?? false);
        $this->termsUrl = (string) ($settings['terms_url'] ?? '');
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

        $this->validate(['termsUrl' => ['nullable', 'url', 'max:255']]);

        $store = app('current_store');
        $existing = $store->settings?->settings_json ?? [];
        $existing['checkout'] = [
            'guest_checkout_enabled' => $this->guestCheckoutEnabled,
            'require_phone' => $this->requirePhone,
            'terms_url' => $this->termsUrl,
        ];

        StoreSettings::query()->updateOrCreate(['store_id' => $store->id], ['settings_json' => $existing]);

        $this->dispatch('toast', type: 'success', message: __('Settings saved'));
    }

    public function render()
    {
        return view('livewire.admin.settings.checkout');
    }
}
