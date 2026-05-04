<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\ShippingZoneResource;
use App\Models\ShippingZone;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ShippingZoneController extends Controller
{
    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($request, $store);

        $zones = ShippingZone::withoutGlobalScopes()
            ->with(['rates' => fn ($query) => $query->withoutGlobalScopes()->orderBy('id'), 'store'])
            ->where('store_id', $store->getKey())
            ->orderBy('id')
            ->get();

        return ShippingZoneResource::collection($zones);
    }

    public function store(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $validated = $this->validatePayload($request, $store);

        $zone = ShippingZone::withoutGlobalScopes()->create([
            'store_id' => $store->getKey(),
            ...$this->attributes($validated),
        ]);

        return ShippingZoneResource::make($this->loadZone($zone))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Store $store, ShippingZone $shippingZone): ShippingZoneResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessZoneBelongsToStore($shippingZone, $store);

        $validated = $this->validatePayload($request, $store, $shippingZone);

        $shippingZone->update($this->attributes($validated));

        return ShippingZoneResource::make($this->loadZone($shippingZone->refresh()));
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('admin_api_oauth_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessZoneBelongsToStore(ShippingZone $zone, Store $store): void
    {
        abort_unless((int) $zone->store_id === $store->getKey(), 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, Store $store, ?ShippingZone $zone = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'countries_json' => ['required', 'array', 'min:1'],
            'countries_json.*' => ['required', 'string', 'size:2'],
            'regions_json' => ['sometimes', 'array'],
            'regions_json.*' => ['string', 'max:20'],
        ]);

        $countries = $this->countryCodes($validated['countries_json']);

        if ($countries === []) {
            throw ValidationException::withMessages([
                'countries_json' => __('Enter at least one ISO country code.'),
            ]);
        }

        $overlap = ShippingZone::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->when($zone instanceof ShippingZone, fn (Builder $query) => $query->whereKeyNot($zone->getKey()))
            ->get()
            ->first(fn (ShippingZone $existing): bool => array_intersect($countries, $existing->countries_json ?? []) !== []);

        if ($overlap instanceof ShippingZone) {
            throw ValidationException::withMessages([
                'countries_json' => __('One or more countries already belong to another shipping zone.'),
            ]);
        }

        $validated['countries_json'] = $countries;
        $validated['regions_json'] = $this->regionCodes($validated['regions_json'] ?? []);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'countries_json' => $validated['countries_json'],
            'regions_json' => $validated['regions_json'],
        ];
    }

    /**
     * @param  list<string>  $countries
     * @return list<string>
     */
    private function countryCodes(array $countries): array
    {
        return collect($countries)
            ->map(fn (string $country): string => strtoupper(trim($country)))
            ->filter(fn (string $country): bool => preg_match('/^[A-Z]{2}$/', $country) === 1)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $regions
     * @return list<string>
     */
    private function regionCodes(array $regions): array
    {
        return collect($regions)
            ->map(fn (string $region): string => strtoupper(trim($region)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function loadZone(ShippingZone $zone): ShippingZone
    {
        return $zone->load([
            'rates' => fn ($query) => $query->withoutGlobalScopes()->orderBy('id'),
            'store',
        ]);
    }
}
