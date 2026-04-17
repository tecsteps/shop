<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ResolvesApiContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Discounts\ListDiscountsRequest;
use App\Http\Requests\Admin\Discounts\StoreDiscountRequest;
use App\Http\Requests\Admin\Discounts\UpdateDiscountRequest;
use App\Http\Resources\Admin\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    use ResolvesApiContext;

    public function index(ListDiscountsRequest $request, int $store): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $validated = $request->validated();

        $query = Discount::query()->where('store_id', $storeId);

        if (isset($validated['type']) && is_string($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['status']) && is_string($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $query->orderByDesc('created_at');

        $perPage = (int) ($validated['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => DiscountResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
                'last_page' => (int) $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreDiscountRequest $request, int $store): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $validated = $request->validated();

        $normalizedCode = isset($validated['code']) && is_string($validated['code'])
            ? Str::upper(trim($validated['code']))
            : null;

        if ($normalizedCode !== null && $normalizedCode !== '' && $this->isDuplicateCode($storeId, $normalizedCode)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'code' => ['The code has already been taken.'],
                ],
            ], 422);
        }

        $service = $this->resolveService('App\\Services\\DiscountService');

        if ($service !== null && method_exists($service, 'create')) {
            try {
                $serviceDiscount = $service->create($storeId, $validated);

                if ($serviceDiscount instanceof Discount) {
                    return response()->json([
                        'data' => DiscountResource::make($serviceDiscount)->resolve(),
                    ], 201);
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $discount = Discount::query()->create([
            'store_id' => $storeId,
            'type' => (string) ($validated['type'] ?? 'code'),
            'code' => $normalizedCode,
            'value_type' => (string) $validated['value_type'],
            'value_amount' => (int) $validated['value_amount'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => isset($validated['usage_limit']) ? (int) $validated['usage_limit'] : null,
            'usage_count' => (int) ($validated['usage_count'] ?? 0),
            'rules_json' => isset($validated['rules_json']) && is_array($validated['rules_json']) ? $validated['rules_json'] : [],
            'status' => (string) ($validated['status'] ?? 'active'),
        ]);

        return response()->json([
            'data' => DiscountResource::make($discount)->resolve(),
        ], 201);
    }

    public function show(Request $request, int $store, int $discount): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $foundDiscount = $this->findStoreDiscount($storeId, $discount);

        if (! $foundDiscount instanceof Discount) {
            return response()->json([
                'message' => 'Discount not found.',
            ], 404);
        }

        return response()->json([
            'data' => DiscountResource::make($foundDiscount)->resolve(),
        ]);
    }

    public function update(UpdateDiscountRequest $request, int $store, int $discount): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $foundDiscount = $this->findStoreDiscount($storeId, $discount);

        if (! $foundDiscount instanceof Discount) {
            return response()->json([
                'message' => 'Discount not found.',
            ], 404);
        }

        $validated = $request->validated();

        if (array_key_exists('code', $validated) && isset($validated['code'])) {
            $newCode = Str::upper(trim((string) $validated['code']));

            if ($newCode !== '' && $newCode !== (string) ($foundDiscount->code ?? '') && $this->isDuplicateCode($storeId, $newCode, (int) $foundDiscount->id)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'code' => ['The code has already been taken.'],
                    ],
                ], 422);
            }

            $validated['code'] = $newCode !== '' ? $newCode : null;
        }

        $service = $this->resolveService('App\\Services\\DiscountService');

        if ($service !== null && method_exists($service, 'update')) {
            try {
                $serviceDiscount = $service->update($foundDiscount, $validated);

                if ($serviceDiscount instanceof Discount) {
                    return response()->json([
                        'data' => DiscountResource::make($serviceDiscount)->resolve(),
                    ]);
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $payload = [];

        foreach (['type', 'code', 'value_type', 'value_amount', 'starts_at', 'ends_at', 'usage_limit', 'usage_count', 'status'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        if (array_key_exists('rules_json', $validated)) {
            $payload['rules_json'] = is_array($validated['rules_json']) ? $validated['rules_json'] : [];
        }

        if ($payload !== []) {
            $foundDiscount->fill($payload);
            $foundDiscount->save();
        }

        return response()->json([
            'data' => DiscountResource::make($foundDiscount)->resolve(),
        ]);
    }

    public function destroy(Request $request, int $store, int $discount): JsonResponse
    {
        $storeId = $this->scopedStoreId($request, $store);
        $foundDiscount = $this->findStoreDiscount($storeId, $discount);

        if (! $foundDiscount instanceof Discount) {
            return response()->json([
                'message' => 'Discount not found.',
            ], 404);
        }

        $service = $this->resolveService('App\\Services\\DiscountService');

        if ($service !== null) {
            try {
                if (method_exists($service, 'delete')) {
                    $service->delete($foundDiscount);
                }
            } catch (\Throwable) {
                // Fall back to direct persistence while service contracts stabilize.
            }
        }

        $foundDiscount->delete();

        return response()->json([
            'message' => 'Discount deleted',
        ]);
    }

    private function findStoreDiscount(int $storeId, int $discountId): ?Discount
    {
        return Discount::query()
            ->where('store_id', $storeId)
            ->whereKey($discountId)
            ->first();
    }

    private function isDuplicateCode(int $storeId, string $code, ?int $ignoreDiscountId = null): bool
    {
        $query = Discount::query()
            ->where('store_id', $storeId)
            ->whereRaw('LOWER(code) = ?', [Str::lower($code)]);

        if ($ignoreDiscountId !== null) {
            $query->where('id', '!=', $ignoreDiscountId);
        }

        return $query->exists();
    }
}
