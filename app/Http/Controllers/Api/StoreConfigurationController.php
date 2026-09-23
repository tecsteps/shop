<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\Theme;
use App\Services\AdminStoreConfigurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreConfigurationController extends Controller
{
    /** @var list<string> */
    private const ISO_COUNTRY_CODES = [
        'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
        'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ',
        'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
        'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR',
        'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY',
        'HK', 'HM', 'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO', 'JP',
        'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ',
        'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
        'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
        'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM',
        'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA',
        'RE', 'RO', 'RS', 'RU', 'RW', 'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
        'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
        'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
    ];

    public function pages(Request $request, int $storeId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-store-settings');
        $filters = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($resources->listPages($store, $filters));
    }

    public function createPage(Request $request, int $storeId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-store-settings');
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('pages', 'handle')->where('store_id', $store->getKey())],
            'body_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published'])],
        ]);
        $page = $resources->createPage($store, $validated);

        return response()->json(['data' => $resources->pageResource($page)], 201);
    }

    public function updatePage(Request $request, int $storeId, int $pageId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-store-settings');
        $page = Page::query()->where('store_id', $store->getKey())->findOrFail($pageId);
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('pages', 'handle')->where('store_id', $store->getKey())->ignore($page->getKey())],
            'body_html' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published'])],
        ]);
        $updated = $resources->updatePage($page, $validated);

        return response()->json(['data' => $resources->pageResource($updated)]);
    }

    public function deletePage(int $storeId, int $pageId): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-store-settings');
        Page::query()->where('store_id', $store->getKey())->findOrFail($pageId)->delete();

        return response()->json(['message' => 'Page deleted']);
    }

    public function shippingZones(int $storeId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-shipping');

        return response()->json(['data' => $resources->listShippingZones($store)]);
    }

    public function createShippingZone(Request $request, int $storeId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-shipping');
        $validated = $this->validateShippingZone($request, $store, $resources);
        $zone = $resources->createShippingZone($store, $validated);

        return response()->json(['data' => $resources->shippingZoneResource($zone->load('rates'))], 201);
    }

    public function updateShippingZone(Request $request, int $storeId, int $zoneId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-shipping');
        $zone = ShippingZone::query()->where('store_id', $store->getKey())->findOrFail($zoneId);
        $validated = $this->validateShippingZone($request, $store, $resources, $zone->getKey());
        $resources->updateShippingZone($zone, $validated);

        return response()->json(['data' => $resources->shippingZoneResource($zone->load('rates'))]);
    }

    public function createShippingRate(Request $request, int $storeId, int $zoneId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-shipping');
        $zone = ShippingZone::query()->where('store_id', $store->getKey())->findOrFail($zoneId);
        $this->requireJsonObjectField($request, 'config_json');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['flat', 'weight', 'price', 'carrier'])],
            'config_json' => ['required', 'array', 'min:1'],
            'config_json.price_amount' => ['required_if:type,flat', 'integer', 'min:0'],
            'config_json.currency' => ['sometimes', 'string', 'size:3'],
            'config_json.tiers' => ['required_if:type,weight,price', 'array', 'min:1'],
            'config_json.tiers.*.min_weight_g' => ['required_if:type,weight', 'integer', 'min:0'],
            'config_json.tiers.*.max_weight_g' => ['sometimes', 'nullable', 'integer', 'gt:config_json.tiers.*.min_weight_g'],
            'config_json.tiers.*.min_order_amount' => ['required_if:type,price', 'integer', 'min:0'],
            'config_json.tiers.*.max_order_amount' => ['sometimes', 'nullable', 'integer', 'gt:config_json.tiers.*.min_order_amount'],
            'config_json.tiers.*.price_amount' => ['required_if:type,weight,price', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['config_json']['currency']) && strtoupper($validated['config_json']['currency']) !== strtoupper($store->default_currency)) {
            throw ValidationException::withMessages(['config_json.currency' => 'The shipping rate currency must match the store currency.']);
        }

        if (isset($validated['config_json']['currency'])) {
            $validated['config_json']['currency'] = strtoupper($validated['config_json']['currency']);
        }

        $rate = $resources->createShippingRate($zone, $validated);

        return response()->json(['data' => [
            'id' => $rate->getKey(),
            'name' => $rate->name,
            'type' => $rate->type,
            'config_json' => $rate->config_json,
            'is_active' => $rate->is_active,
        ]], 201);
    }

    public function createTheme(Request $request, int $storeId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        Gate::authorize('manage-themes');
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:zip', 'extensions:zip', 'max:51200'],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        $data = $resources->installTheme($store, $validated['file'], $validated['name'] ?? null);

        return response()->json(['data' => $data], 201);
    }

    public function publishTheme(int $storeId, int $themeId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        $theme = Theme::query()->where('store_id', $store->getKey())->findOrFail($themeId);
        Gate::authorize('manage-themes');

        return response()->json(['data' => $resources->publishTheme($theme)]);
    }

    public function updateThemeSettings(Request $request, int $storeId, int $themeId, AdminStoreConfigurationService $resources): JsonResponse
    {
        $store = $this->store($storeId);
        $theme = Theme::query()->where('store_id', $store->getKey())->findOrFail($themeId);
        Gate::authorize('manage-themes');
        $this->requireJsonObjectField($request, 'settings_json');
        $validated = $request->validate(['settings_json' => ['present', 'array']]);

        return response()->json(['data' => $resources->updateThemeSettings($theme, $validated['settings_json'])]);
    }

    /** @return array{name: string, countries_json: list<string>, regions_json?: list<string>} */
    private function validateShippingZone(Request $request, Store $store, AdminStoreConfigurationService $resources, ?int $zoneId = null): array
    {
        $countries = $request->input('countries_json');

        if (is_array($countries)) {
            $countries = array_map(static fn (mixed $country): mixed => is_string($country) ? strtoupper(trim($country)) : $country, $countries);
            $request->merge(['countries_json' => $countries]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'countries_json' => ['required', 'array', 'min:1'],
            'countries_json.*' => ['required', 'string', 'size:2', 'distinct:strict', Rule::in(self::ISO_COUNTRY_CODES)],
            'regions_json' => ['sometimes', 'array'],
            'regions_json.*' => ['required', 'string', 'max:100'],
        ]);

        if ($resources->countryOverlap($store, $validated['countries_json'], $zoneId)) {
            throw ValidationException::withMessages(['countries_json' => 'One or more countries already belong to another shipping zone.']);
        }

        return $validated;
    }

    private function requireJsonObjectField(Request $request, string $field): void
    {
        $body = json_decode($request->getContent());

        if (! is_object($body) || ! isset($body->{$field}) || ! is_object($body->{$field})) {
            throw ValidationException::withMessages([$field => "The {$field} field must be an object."]);
        }
    }

    private function store(int $storeId): Store
    {
        return Store::query()->findOrFail($storeId);
    }
}
