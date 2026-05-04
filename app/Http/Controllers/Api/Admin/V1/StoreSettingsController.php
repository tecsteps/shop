<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\StoreSettingsResource;
use App\Models\Store;
use App\Models\StoreSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreSettingsController extends Controller
{
    public function show(Request $request, Store $store): StoreSettingsResource
    {
        $this->authorizeStore($request, $store);
        $this->settings($store);

        return StoreSettingsResource::make($this->loadStore($store));
    }

    public function update(Request $request, Store $store): StoreSettingsResource
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'default_currency' => ['sometimes', 'required', Rule::in($this->currencyOptions())],
            'default_locale' => ['sometimes', 'required', Rule::in(array_keys($this->localeOptions()))],
            'timezone' => ['sometimes', 'required', Rule::in(timezone_identifiers_list())],
            'settings_json' => ['sometimes', 'array'],
            'settings_json.announcement' => ['sometimes', 'array'],
            'settings_json.announcement.enabled' => ['sometimes', 'boolean'],
            'settings_json.announcement.text' => ['nullable', 'string', 'max:255'],
            'settings_json.checkout' => ['sometimes', 'array'],
            'settings_json.checkout.guest_checkout_enabled' => ['sometimes', 'boolean'],
            'settings_json.checkout.customer_accounts_required' => ['sometimes', 'boolean'],
            'settings_json.checkout.phone_number_required' => ['sometimes', 'boolean'],
            'settings_json.checkout.billing_address_enabled' => ['sometimes', 'boolean'],
            'settings_json.checkout.order_notes_enabled' => ['sometimes', 'boolean'],
            'settings_json.checkout.terms_required' => ['sometimes', 'boolean'],
            'settings_json.checkout.terms_url' => ['nullable', 'url', 'max:2048'],
            'settings_json.checkout.payment_hold_hours' => ['sometimes', 'integer', 'min:1', 'max:168'],
            'settings_json.checkout.abandoned_checkout_days' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'settings_json.bank_transfer_cancel_days' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'settings_json.notifications' => ['sometimes', 'array'],
            'settings_json.notifications.sender_name' => ['nullable', 'string', 'max:255'],
            'settings_json.notifications.sender_email' => ['nullable', 'email', 'max:255'],
            'settings_json.notifications.reply_to_email' => ['nullable', 'email', 'max:255'],
            'settings_json.notifications.order_confirmation_enabled' => ['sometimes', 'boolean'],
            'settings_json.notifications.shipping_confirmation_enabled' => ['sometimes', 'boolean'],
            'settings_json.notifications.refund_confirmation_enabled' => ['sometimes', 'boolean'],
            'settings_json.notifications.admin_order_alerts_enabled' => ['sometimes', 'boolean'],
            'settings_json.notifications.low_stock_alerts_enabled' => ['sometimes', 'boolean'],
            'settings_json.notifications.low_stock_threshold' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ]);

        $settingsPayload = $request->input('settings_json');

        if ($request->exists('settings_json') && is_array($settingsPayload)) {
            $this->validateSettingsObject($settingsPayload);
        }

        $storeAttributes = Arr::only($validated, [
            'name',
            'default_currency',
            'default_locale',
            'timezone',
        ]);

        if ($storeAttributes !== []) {
            $store->forceFill($storeAttributes)->save();
        }

        if ($request->exists('settings_json') && is_array($settingsPayload)) {
            $settings = $this->settings($store);
            $settings->forceFill([
                'settings_json' => array_replace_recursive($settings->settings_json ?? [], $settingsPayload),
                'updated_at' => now(),
            ])->save();
        }

        return StoreSettingsResource::make($this->loadStore($store->refresh()));
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('admin_api_oauth_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function settings(Store $store): StoreSettings
    {
        return StoreSettings::query()->firstOrCreate(
            ['store_id' => $store->getKey()],
            ['settings_json' => []],
        );
    }

    private function loadStore(Store $store): Store
    {
        return $store->load([
            'settings',
            'domains' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('hostname'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateSettingsObject(array $settings): void
    {
        if ($settings !== [] && array_is_list($settings)) {
            throw ValidationException::withMessages([
                'settings_json' => __('The settings json field must be an object.'),
            ]);
        }

        foreach (['announcement', 'checkout', 'notifications'] as $section) {
            if (array_key_exists($section, $settings) && is_array($settings[$section]) && $settings[$section] !== [] && array_is_list($settings[$section])) {
                throw ValidationException::withMessages([
                    "settings_json.{$section}" => __('The :section settings must be an object.', ['section' => str_replace('_', ' ', $section)]),
                ]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function localeOptions(): array
    {
        return [
            'en' => 'English',
            'de' => 'German',
            'fr' => 'French',
        ];
    }

    /**
     * @return list<string>
     */
    private function currencyOptions(): array
    {
        return ['EUR', 'USD', 'GBP', 'CHF'];
    }
}
