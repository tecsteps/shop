<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class DiscountController extends Controller
{
    public function index(Request $request, int $storeId): JsonResponse
    {
        $data = $request->validate(['type' => ['sometimes', 'in:code,automatic'], 'status' => ['sometimes', 'in:draft,active,expired,disabled'], 'per_page' => ['sometimes', 'integer', 'max:100']]);
        $items = Discount::withoutGlobalScopes()->where('store_id', $storeId)
            ->when(isset($data['type']), fn ($q) => $q->where('type', $data['type']))
            ->when(isset($data['status']), fn ($q) => $q->where('status', $data['status']))
            ->latest()->paginate($data['per_page'] ?? 25);

        return response()->json(['data' => $items->items(), 'meta' => ['total' => $items->total()]]);
    }

    public function store(Request $request, int $storeId): JsonResponse
    {
        $discount = Discount::withoutGlobalScopes()->create(['store_id' => $storeId, ...$this->data($request, $storeId)]);

        return response()->json(['data' => $discount], 201);
    }

    public function update(Request $request, int $storeId, int $discountId): JsonResponse
    {
        $discount = $this->find($storeId, $discountId);
        $discount->update($this->data($request, $storeId, $discountId, true));

        return response()->json(['data' => $discount->refresh()]);
    }

    public function destroy(int $storeId, int $discountId): JsonResponse
    {
        $this->find($storeId, $discountId)->delete();

        return response()->json(['message' => 'Discount deleted.']);
    }

    /** @return array<string, mixed> */
    private function data(Request $request, int $storeId, ?int $ignore = null, bool $partial = false): array
    {
        return $request->validate([
            'type' => [$partial ? 'sometimes' : 'required', 'in:code,automatic'],
            'code' => ['nullable', 'string', Rule::unique('discounts')->where('store_id', $storeId)->ignore($ignore)],
            'value_type' => [$partial ? 'sometimes' : 'required', 'in:fixed,percent,free_shipping'],
            'value_amount' => [$partial ? 'sometimes' : 'required', 'integer', 'min:0'],
            'starts_at' => [$partial ? 'sometimes' : 'required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'rules_json' => ['sometimes', 'array'],
            'status' => ['sometimes', 'in:draft,active,expired,disabled'],
        ]);
    }

    private function find(int $storeId, int $id): Discount
    {
        return Discount::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($id);
    }
}
