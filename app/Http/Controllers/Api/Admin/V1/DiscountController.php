<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\V1\DiscountResource;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DiscountController extends Controller
{
    public function index(Request $request, Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($request, $store);

        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['code', 'automatic'])],
            'status' => ['nullable', Rule::in(['active', 'expired', 'scheduled'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $discounts = Discount::withoutGlobalScopes()
            ->where('store_id', $store->getKey())
            ->when(data_get($validated, 'type'), fn (Builder $query, string $type) => $query->where('type', $type))
            ->when(data_get($validated, 'status'), fn (Builder $query, string $status) => $this->applyComputedStatus($query, $status))
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) data_get($validated, 'per_page', 25));

        return DiscountResource::collection($discounts);
    }

    public function store(Request $request, Store $store): JsonResponse
    {
        $this->authorizeStore($request, $store);

        $validated = $this->validatePayload($request, $store);

        $discount = Discount::withoutGlobalScopes()->create($this->attributesForCreate($validated, $store));

        return DiscountResource::make($discount)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Store $store, Discount $discount): DiscountResource
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessDiscountBelongsToStore($discount, $store);

        $validated = $this->validatePayload($request, $store, $discount);

        $discount->update($this->attributesForUpdate($validated, $discount));

        return DiscountResource::make($discount->refresh());
    }

    public function destroy(Request $request, Store $store, Discount $discount): JsonResponse
    {
        $this->authorizeStore($request, $store);
        $this->abortUnlessDiscountBelongsToStore($discount, $store);

        $discount->delete();

        return response()->json(['message' => 'Discount deleted']);
    }

    private function authorizeStore(Request $request, Store $store): void
    {
        if (! $request->attributes->has('sanctum_personal_access_token')) {
            abort_unless($request->user()?->stores()->whereKey($store->getKey())->exists(), 403);
        }

        app()->instance('current_store', $store);
    }

    private function abortUnlessDiscountBelongsToStore(Discount $discount, Store $store): void
    {
        abort_unless((int) $discount->store_id === $store->getKey(), 404);
    }

    private function applyComputedStatus(Builder $query, string $status): void
    {
        match ($status) {
            'active' => $query
                ->where('status', 'active')
                ->where('starts_at', '<=', now())
                ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now())),
            'expired' => $query->where(fn (Builder $query) => $query->where('status', 'expired')->orWhere('ends_at', '<=', now())),
            'scheduled' => $query->where('starts_at', '>', now()),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, Store $store, ?Discount $discount = null): array
    {
        $creating = $discount === null;
        $type = (string) $request->input('type', $discount?->type->value ?? 'code');
        $valueType = (string) $request->input('value_type', $discount?->value_type->value ?? 'percent');

        $validated = $request->validate([
            'type' => [$creating ? 'required' : 'sometimes', Rule::in(['code', 'automatic'])],
            'code' => [
                Rule::requiredIf($creating && $type === 'code'),
                'nullable',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail) use ($store, $discount): void {
                    if ($value === null || trim((string) $value) === '') {
                        return;
                    }

                    $normalized = Str::upper(trim((string) $value));

                    if ($discount instanceof Discount && $normalized !== (string) $discount->code) {
                        $fail(__('The discount code cannot be changed after creation.'));

                        return;
                    }

                    $exists = Discount::withoutGlobalScopes()
                        ->where('store_id', $store->getKey())
                        ->whereRaw('lower(code) = ?', [Str::lower($normalized)])
                        ->when($discount instanceof Discount, fn (Builder $query) => $query->whereKeyNot($discount->getKey()))
                        ->exists();

                    if ($exists) {
                        $fail(__('The discount code has already been taken.'));
                    }
                },
            ],
            'value_type' => [$creating ? 'required' : 'sometimes', Rule::in(['fixed', 'percent', 'free_shipping'])],
            'value_amount' => $this->valueAmountRules($creating, $valueType),
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => [
                'sometimes',
                'nullable',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $discount): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $startsAt = $request->input('starts_at', $discount?->starts_at ?? now());

                    if (Carbon::parse($value)->lte(Carbon::parse($startsAt))) {
                        $fail(__('The end date must be after the start date.'));
                    }
                },
            ],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'expired', 'disabled'])],
            'rules_json' => ['sometimes', 'array'],
            'rules_json.minimum_purchase_amount' => ['nullable', 'integer', 'min:0'],
            'rules_json.min_purchase_amount' => ['nullable', 'integer', 'min:0'],
            'rules_json.applicable_product_ids' => ['nullable', 'array'],
            'rules_json.applicable_product_ids.*' => ['integer', Rule::exists('products', 'id')->where('store_id', $store->getKey())],
            'rules_json.applicable_collection_ids' => ['nullable', 'array'],
            'rules_json.applicable_collection_ids.*' => ['integer', Rule::exists('collections', 'id')->where('store_id', $store->getKey())],
            'rules_json.customer_eligibility' => ['nullable', Rule::in(['all', 'specific_customers', 'specific_segments'])],
            'rules_json.once_per_customer' => ['nullable', 'boolean'],
            'rules_json.one_per_customer' => ['nullable', 'boolean'],
        ]);

        return $validated;
    }

    /**
     * @return list<mixed>
     */
    private function valueAmountRules(bool $creating, string $valueType): array
    {
        $presence = $creating ? 'required' : 'sometimes';

        return match ($valueType) {
            'percent' => [$presence, 'integer', 'min:1', 'max:100'],
            'free_shipping' => ['sometimes', 'nullable', 'integer', 'min:0'],
            default => [$presence, 'integer', 'min:1'],
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesForCreate(array $validated, Store $store): array
    {
        $type = (string) $validated['type'];
        $valueType = (string) $validated['value_type'];

        return [
            'store_id' => $store->getKey(),
            'type' => $type,
            'code' => $type === 'code' ? Str::upper(trim((string) $validated['code'])) : null,
            'value_type' => $valueType,
            'value_amount' => $valueType === 'free_shipping' ? 0 : (int) $validated['value_amount'],
            'starts_at' => $validated['starts_at'] ?? now(),
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'usage_count' => 0,
            'rules_json' => $this->rulesPayload($validated['rules_json'] ?? []),
            'status' => $validated['status'] ?? 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributesForUpdate(array $validated, Discount $discount): array
    {
        $attributes = [];
        $valueType = (string) data_get($validated, 'value_type', $discount->value_type->value);

        foreach (['type', 'value_type', 'starts_at', 'ends_at', 'usage_limit', 'status'] as $field) {
            if (array_key_exists($field, $validated)) {
                $attributes[$field] = $validated[$field];
            }
        }

        if (array_key_exists('value_amount', $validated)) {
            $attributes['value_amount'] = $valueType === 'free_shipping' ? 0 : (int) $validated['value_amount'];
        } elseif (array_key_exists('value_type', $validated) && $valueType === 'free_shipping') {
            $attributes['value_amount'] = 0;
        }

        if (array_key_exists('rules_json', $validated)) {
            $attributes['rules_json'] = $this->rulesPayload($validated['rules_json']);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function rulesPayload(array $rules): array
    {
        return [
            'min_purchase_amount' => (int) data_get($rules, 'min_purchase_amount', data_get($rules, 'minimum_purchase_amount', 0)),
            'applicable_product_ids' => $this->integerList(data_get($rules, 'applicable_product_ids', [])),
            'applicable_collection_ids' => $this->integerList(data_get($rules, 'applicable_collection_ids', [])),
            'customer_eligibility' => data_get($rules, 'customer_eligibility', 'all'),
            'one_per_customer' => (bool) data_get($rules, 'one_per_customer', data_get($rules, 'once_per_customer', false)),
        ];
    }

    /**
     * @return list<int>
     */
    private function integerList(mixed $value): array
    {
        return collect(is_array($value) ? $value : [])
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
