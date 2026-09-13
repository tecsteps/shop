<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Enums\DiscountStatus;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\StoreDiscountRequest;
use App\Http\Requests\Api\Admin\UpdateDiscountRequest;
use App\Http\Resources\Api\Admin\DiscountResource;
use App\Models\Discount;
use App\Tenancy\CurrentStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DiscountController extends Controller
{
    public function __construct(protected CurrentStore $currentStore) {}

    public function index(Request $request, int $storeId): JsonResponse
    {
        Gate::authorize('viewAny', Discount::class);

        $validated = $request->validate([
            'type' => 'nullable|in:code,automatic',
            'status' => 'nullable|in:draft,active,expired,disabled,scheduled',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Discount::query()
            ->where('store_id', $this->currentStore->id())
            ->orderByDesc('updated_at')
            ->orderBy('id');

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (! empty($validated['status'])) {
            $status = $validated['status'];

            if ($status === 'scheduled') {
                $query->where('starts_at', '>', now());
            } else {
                $query->where('status', $status);
            }
        }

        $paginator = $query->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'data' => DiscountResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreDiscountRequest $request, int $storeId): JsonResponse
    {
        Gate::authorize('create', Discount::class);

        $validated = $request->validated();

        $discount = Discount::query()->create([
            'store_id' => $this->currentStore->id(),
            'type' => DiscountType::from($validated['type']),
            'code' => $validated['code'] ?? null,
            'value_type' => DiscountValueType::from($validated['value_type']),
            'value_amount' => $validated['value_amount'],
            'starts_at' => $validated['starts_at'] ?? now(),
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'usage_count' => 0,
            'rules_json' => $validated['rules_json'] ?? [],
            'status' => isset($validated['status']) ? DiscountStatus::from($validated['status']) : DiscountStatus::Active,
        ]);

        return (new DiscountResource($discount))->response()->setStatusCode(201);
    }

    public function show(int $storeId, int $discountId): JsonResponse
    {
        $discount = $this->findDiscount($discountId);

        Gate::authorize('view', $discount);

        return (new DiscountResource($discount))->response();
    }

    public function update(UpdateDiscountRequest $request, int $storeId, int $discountId): JsonResponse
    {
        $discount = $this->findDiscount($discountId);

        Gate::authorize('update', $discount);

        $validated = $request->validated();

        foreach (['value_amount', 'starts_at', 'ends_at', 'usage_limit', 'rules_json'] as $field) {
            if (array_key_exists($field, $validated)) {
                $discount->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('value_type', $validated) && $validated['value_type'] !== null) {
            $discount->value_type = DiscountValueType::from($validated['value_type']);
        }

        if (array_key_exists('status', $validated) && $validated['status'] !== null) {
            $discount->status = DiscountStatus::from($validated['status']);
        }

        $discount->save();

        return (new DiscountResource($discount->refresh()))->response();
    }

    public function destroy(int $storeId, int $discountId): JsonResponse
    {
        $discount = $this->findDiscount($discountId);

        Gate::authorize('delete', $discount);

        $discount->delete();

        return response()->json(['message' => 'Discount deleted']);
    }

    protected function findDiscount(int $discountId): Discount
    {
        return Discount::query()
            ->where('store_id', $this->currentStore->id())
            ->whereKey($discountId)
            ->firstOrFail();
    }
}
