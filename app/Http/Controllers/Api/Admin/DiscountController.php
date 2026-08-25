<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request, int $storeId)
    {
        $this->authorize('viewAny', Discount::class);

        $discounts = Discount::query()
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->paginate($request->per_page ?? 25);

        return response()->json([
            'data' => $discounts->map(fn (Discount $d) => [
                'id' => $d->id,
                'type' => $d->type,
                'code' => $d->code,
                'value_type' => $d->value_type,
                'value_amount' => $d->value_amount,
                'starts_at' => $d->starts_at?->toISOString(),
                'ends_at' => $d->ends_at?->toISOString(),
                'usage_limit' => $d->usage_limit,
                'usage_count' => $d->usage_count,
                'status' => $d->status,
            ]),
            'meta' => [
                'current_page' => $discounts->currentPage(),
                'per_page' => $discounts->perPage(),
                'total' => $discounts->total(),
                'last_page' => $discounts->lastPage(),
            ],
        ]);
    }

    public function store(Request $request, int $storeId)
    {
        $this->authorize('create', Discount::class);

        $validated = $request->validate([
            'type' => ['required', 'in:code,automatic'],
            'code' => ['required_if:type,code', 'nullable', 'string', 'max:50'],
            'value_type' => ['required', 'in:fixed,percent,free_shipping'],
            'value_amount' => ['required_unless:value_type,free_shipping', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'rules_json' => ['sometimes', 'array'],
        ]);

        $discount = Discount::create([
            'store_id' => app('current_store')->id,
            'type' => $validated['type'],
            'code' => $validated['code'] ?? null,
            'value_type' => $validated['value_type'],
            'value_amount' => $validated['value_amount'] ?? 0,
            'starts_at' => $validated['starts_at'] ?? now(),
            'ends_at' => $validated['ends_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'usage_count' => 0,
            'rules_json' => $validated['rules_json'] ?? [],
            'status' => 'active',
        ]);

        return response()->json(['data' => ['id' => $discount->id, 'code' => $discount->code]], 201);
    }

    public function update(Request $request, int $storeId, int $discountId)
    {
        $discount = Discount::findOrFail($discountId);
        $this->authorize('update', $discount);

        $validated = $request->validate([
            'value_type' => ['sometimes', 'in:fixed,percent,free_shipping'],
            'value_amount' => ['sometimes', 'integer', 'min:0'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
            'usage_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:active,disabled'],
            'rules_json' => ['sometimes', 'array'],
        ]);

        $discount->update($validated);

        return response()->json(['data' => ['id' => $discount->id, 'status' => $discount->status]]);
    }

    public function destroy(int $storeId, int $discountId)
    {
        $discount = Discount::findOrFail($discountId);
        $this->authorize('delete', $discount);

        $discount->delete();

        return response()->json(['message' => 'Discount deleted']);
    }
}
