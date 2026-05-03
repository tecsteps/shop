<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListDiscountsRequest;
use App\Http\Requests\Admin\StoreDiscountRequest;
use App\Http\Requests\Admin\UpdateDiscountRequest;
use App\Http\Resources\Admin\DiscountResource;
use App\Models\Discount;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;

class DiscountController extends Controller
{
    public function index(ListDiscountsRequest $request, Store $store): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->when($validated['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('created_at');

        return DiscountResource::collection(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function store(StoreDiscountRequest $request, Store $store): JsonResponse
    {
        $validated = $request->validated();
        $discount = Discount::withoutGlobalScopes()->create([
            ...Arr::only($validated, [
                'type',
                'code',
                'value_type',
                'value_amount',
                'starts_at',
                'ends_at',
                'usage_limit',
                'rules_json',
                'status',
            ]),
            'store_id' => $store->id,
            'rules_json' => $validated['rules_json'] ?? [],
        ]);

        return (new DiscountResource($discount))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateDiscountRequest $request, Store $store, int $discount): DiscountResource
    {
        $existingDiscount = $this->findDiscount($store, $discount);
        $existingDiscount->update(Arr::only($request->validated(), [
            'type',
            'value_type',
            'value_amount',
            'starts_at',
            'ends_at',
            'usage_limit',
            'rules_json',
            'status',
        ]));

        return new DiscountResource($existingDiscount->refresh());
    }

    public function destroy(Store $store, int $discount): JsonResponse
    {
        $this->findDiscount($store, $discount)->delete();

        return response()->json(['message' => 'Discount deleted']);
    }

    private function findDiscount(Store $store, int $discountId): Discount
    {
        return Discount::withoutGlobalScopes()
            ->where('store_id', $store->id)
            ->whereKey($discountId)
            ->firstOrFail();
    }
}
